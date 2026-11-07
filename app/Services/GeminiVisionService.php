<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Transaction;
use App\Payment\PaymentManager;
use App\Payment\PaymentProvider;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeminiVisionService
{
    protected ?string $cachedPrompt = null;

    public function __construct(
        protected PaymentManager $paymentManager,
    ) {}

    /**
     * @return array{
     *     provider: ?PaymentProvider,
     *     trx_id: ?string,
     *     amount: ?float,
     *     fee: ?float,
     *     total_amount: ?float,
     *     balance: ?float,
     *     txn_type: ?string,
     *     beneficiary_bank: ?string,
     *     sender_account: ?string,
     *     sender_number: ?string,
     *     sender_name: ?string,
     *     receiver_account: ?string,
     *     receiver_number: ?string,
     *     receiver_name: ?string,
     *     reference: ?string,
     *     transaction_time: ?string,
     *     status_in_screenshot: string,
     *     is_suspicious_or_edited: bool,
     *     confidence_score: float,
     *     notes: ?string,
     *     raw_response?: array<string, mixed>
     * }
     */
    public function analyzeScreenshot(string $screenshotPath): array
    {
        $apiKey = config('payment.gemini.api_key');
        $model = config('payment.gemini.model', 'gemini-flash-lite-latest');
        $baseUrl = config('payment.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        if (empty($apiKey)) {
            Log::warning('GEMINI_API_KEY is not configured in .env. Skipping automated AI screenshot extraction.');

            return $this->fallbackResponse('Gemini API key is not configured.');
        }

        $imageDetails = $this->resolveImageData($screenshotPath);
        if (! $imageDetails) {
            return $this->fallbackResponse("Screenshot file not found at: {$screenshotPath}");
        }

        $prompt = $this->buildPrompt();

        try {
            $endpoint = "{$baseUrl}/models/{$model}:generateContent";

            $response = Http::withHeader('x-goog-api-key', $apiKey)
                ->connectTimeout(10)
                ->timeout(30)
                ->retry(2, 500, null, false)
                ->post($endpoint, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $imageDetails['mime_type'],
                                        'data' => base64_encode($imageDetails['bytes']),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'temperature' => 0.1,
                        'max_output_tokens' => 1024,
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('Gemini API call failed', [
                    'status' => $response->status(),
                ]);

                return $this->fallbackResponse('Gemini API returned HTTP '.$response->status().'.');
            }

            $responseData = $response->json();
            $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $decoded = json_decode($rawText, true);

            if (! is_array($decoded)) {
                Log::error('Failed to decode Gemini response JSON', ['raw_text' => $rawText]);

                return $this->fallbackResponse('Invalid JSON returned by Gemini');
            }

            $rawProvider = $decoded['provider'] ?? null;
            $beneficiaryBank = $decoded['beneficiary_bank'] ?? null;
            $txnType = $decoded['txn_type'] ?? $decoded['transaction_type'] ?? null;
            $notes = (string) ($decoded['notes'] ?? '');

            // Resolve the PaymentProvider instance dynamically via PaymentManager and driver aliases
            $provider = $this->resolvePaymentProvider($rawProvider, $beneficiaryBank, $txnType, $notes);

            $reference = $decoded['reference'] ?? $decoded['ref'] ?? null;
            $rawTrxId = $decoded['trx_id'] ?? $decoded['transaction_id'] ?? null;

            // For bank transfers where TrxID is omitted, reference number serves as the identifier
            if ($rawTrxId === null && $reference !== null && ($provider?->getType() === 'ibanking' || $beneficiaryBank !== null)) {
                $rawTrxId = $reference;
            }

            $trxId = $rawTrxId !== null ? strtoupper(trim((string) $rawTrxId)) : null;
            if ($trxId !== null) {
                $trxId = preg_replace('/[^A-Z0-9]/', '', $trxId);
            }

            $senderAccount = $this->normalizeAccount($decoded['sender_account'] ?? $decoded['from_account'] ?? $decoded['sender_number'] ?? null);
            $receiverAccount = $this->normalizeAccount($decoded['receiver_account'] ?? $decoded['to_account'] ?? $decoded['receiver_number'] ?? null);

            return [
                'provider' => $provider,
                'trx_id' => $trxId,
                'amount' => isset($decoded['amount']) ? (float) $decoded['amount'] : null,
                'fee' => isset($decoded['fee']) ? (float) $decoded['fee'] : 0.0,
                'total_amount' => isset($decoded['total_amount']) ? (float) $decoded['total_amount'] : null,
                'balance' => isset($decoded['balance']) ? (float) $decoded['balance'] : null,
                'txn_type' => $decoded['txn_type'] ?? $decoded['transaction_type'] ?? null,
                'beneficiary_bank' => $decoded['beneficiary_bank'] ?? null,
                'sender_account' => $senderAccount,
                'sender_number' => $senderAccount,
                'sender_name' => $decoded['sender_name'] ?? $decoded['from_name'] ?? null,
                'receiver_account' => $receiverAccount,
                'receiver_number' => $receiverAccount,
                'receiver_name' => $decoded['receiver_name'] ?? $decoded['to_name'] ?? $decoded['beneficiary_name'] ?? null,
                'reference' => $reference,
                'transaction_time' => $decoded['transaction_time'] ?? null,
                'status_in_screenshot' => $decoded['status_in_screenshot'] ?? 'unknown',
                'is_suspicious_or_edited' => (bool) ($decoded['is_suspicious_or_edited'] ?? false),
                'confidence_score' => (float) ($decoded['confidence_score'] ?? 0.0),
                'notes' => $decoded['notes'] ?? null,
                'raw_response' => $decoded,
            ];
        } catch (Exception $e) {
            Log::error('GeminiVisionService could not contact the Gemini API.', [
                'exception' => $e::class,
            ]);

            return $this->fallbackResponse('Gemini API request failed before a response was received.');
        }
    }

    /**
     * Build the verification auditor prompt dynamically by inspecting all registered payment drivers.
     */
    public function buildPrompt(): string
    {
        if ($this->cachedPrompt !== null) {
            return $this->cachedPrompt;
        }

        $drivers = collect($this->paymentManager->getDrivers())
            ->filter(fn ($d) => $d instanceof PaymentProvider);

        $mfsDrivers = $drivers->filter(fn (PaymentProvider $d) => $d->getType() === 'mobile');
        $bankDrivers = $drivers->filter(fn (PaymentProvider $d) => $d->getType() === 'ibanking');

        $mfsDescription = $mfsDrivers->isNotEmpty()
            ? $mfsDrivers->map(function (PaymentProvider $d): string {
                $aliases = implode(', ', array_slice($d->getAliases(), 0, 5));

                return "{$d->getName()} (ID: \"{$d->getId()}\"; aliases: {$aliases})";
            })->implode("\n- ")
            : 'bKash, Nagad, Rocket, Upay';

        $bankDescription = $bankDrivers->isNotEmpty()
            ? $bankDrivers->map(function (PaymentProvider $d): string {
                $aliases = implode(', ', array_slice($d->getAliases(), 0, 6));

                return "{$d->getName()} (ID: \"{$d->getId()}\"; aliases: {$aliases})";
            })->implode("\n- ")
            : 'City Bank, Eastern Bank, Dutch-Bangla Bank, BRAC Bank';

        $allowedIds = $drivers->isNotEmpty()
            ? $drivers->map(fn (PaymentProvider $d) => '"'.$d->getId().'"')->implode(', ')
            : '"bank-city", "bank-ebl", "bkash-personal", "nagad-personal"';

        $customNotes = $drivers->map(function (PaymentProvider $d): ?string {
            $notes = $d->getReceiptPromptNotes();

            return $notes ? "- {$d->getName()} ({$d->getId()}): {$notes}" : null;
        })->filter()->implode("\n");

        return $this->cachedPrompt = <<<PROMPT
You are an expert financial verification auditor analyzing transaction confirmation proofs from Bangladesh:
- Mobile Financial Services (MFS):
- {$mfsDescription}
- Internet Banking Apps, Bank Transfers & Bank Receipts:
- {$bankDescription}
- Physical ATM / CRM / CDM Cash Deposit Printed Slips

Inspect the entire image carefully. Extract every visible transaction field; do not omit a field that is clearly printed.
Never infer, invent, or replace a printed value with the current date/time or the image upload/server receipt time.
Preserve account numbers exactly as displayed, including masking characters such as *, x, or X.

CRITICAL PROVIDER RULE FOR MFS-TO-BANK TRANSFERS (e.g. bKash to Bank / "বিকাশ টু ব্যাংক", Nagad to Bank):
When funds are transferred from an MFS app to a bank account:
The receiving/beneficiary bank is the provider! The merchant/recipient receives the funds in their bank account.
Therefore, "provider" MUST BE the receiving bank provider ID, NOT the sending MFS app:
Always identify the provider as the destination bank where funds were received!

Map account labels exactly:
- "From", "From Account", "Sender", or "Source Account" -> sender_account and sender_name.
- "To", "To Account", "Transferred to", "Beneficiary", or "Recipient" -> receiver_account and receiver_name.
- Do not swap sender and receiver, and do not put account numbers only in notes.

Reference and transaction identifier mapping:
{$customNotes}
- For other providers, put explicitly labeled TrxID/TxnID/Transaction ID/TXN NO/Reference No in trx_id; keep a separately labeled reference in reference. If no separate trx_id exists but a reference number is printed, set both trx_id and reference.

For transaction_time, use the date and time printed on the receipt (Bangladesh local time, Asia/Dhaka), normalized to "YYYY-MM-DD HH:mm:ss". All example receipts include a time. Do not use the phone status-bar time, file metadata, upload time, or current server time.
For amount, use the transfer/deposit amount. Put any service charge and VAT in fee, and the full debit/grand total in total_amount when shown.

Return a STRICT JSON object with these fields:
- "provider": string (one of: {$allowedIds}, or "other")
- "trx_id": string or null (Primary Transaction ID / Reference No)
- "reference": string or null (Reference Number)
- "amount": decimal number (The transfer or deposit amount in BDT)
- "fee": decimal number (Service charge / fee, otherwise 0.0)
- "total_amount": decimal number (Total including fee)
- "balance": decimal number or null (Account balance shown on the receipt, otherwise null)
- "txn_type": string or null (Transaction type, transfer channel, or deposit type as printed, e.g. "বিকাশ টু ব্যাংক")
- "beneficiary_bank": string or null (Beneficiary bank name if shown, e.g. "সিটি ব্যাংক", "Eastern Bank")
- "sender_account": string or null (From/Sender account identifier exactly as displayed)
- "sender_number": string or null (Compatibility alias for sender_account; return the same value)
- "sender_name": string or null (Name explicitly shown for the From/Sender account)
- "receiver_account": string or null (To/Transferred to/Beneficiary account identifier exactly as displayed)
- "receiver_number": string or null (Compatibility alias for receiver_account; return the same value)
- "receiver_name": string or null (Beneficiary name)
- "transaction_time": string or null ("YYYY-MM-DD HH:mm:ss" or null)
- "status_in_screenshot": string ("successful", "completed", "pending", "failed", "unknown")
- "is_suspicious_or_edited": boolean (true if Photoshop artifacts, altered fonts, or fake receipt detected; false otherwise)
- "confidence_score": float between 0.0 and 1.0 (0.0 if image is blank/black/unreadable)
- "notes": string summary of extracted data
PROMPT;
    }

    /**
     * Dynamically resolve the matching PaymentProvider instance from extracted receipt fields.
     */
    protected function resolvePaymentProvider(
        ?string $rawProvider,
        ?string $beneficiaryBank,
        ?string $txnType,
        string $notes,
    ): ?PaymentProvider {
        // 1. If beneficiary bank is present or txn_type indicates transfer to bank, prioritize matching ibanking
        $isTransferToBank = ! empty($beneficiaryBank)
            || ($txnType !== null && (str_contains($txnType, 'ব্যাংক') || str_contains(strtolower($txnType), 'bank')))
            || str_contains($notes, 'bank')
            || str_contains($notes, 'ব্যাংক');

        if ($isTransferToBank) {
            $bankProvider = $this->paymentManager->resolveProviderFromText('ibanking', $beneficiaryBank, $txnType, $notes, $rawProvider);
            if ($bankProvider instanceof PaymentProvider) {
                return $bankProvider;
            }
        }

        // 2. Direct lookup of rawProvider
        if (! empty($rawProvider)) {
            $matched = $this->paymentManager->findProvider($rawProvider);
            if ($matched instanceof PaymentProvider) {
                return $matched;
            }
        }

        // 3. General fallback resolve from all extracted texts
        return $this->paymentManager->resolveProviderFromText(null, $beneficiaryBank, $txnType, $notes, $rawProvider);
    }

    /**
     * @return array{mime_type: string, bytes: string}|null
     */
    private function resolveImageData(string $path): ?array
    {
        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            $bytes = $disk->get($path);
            if (! is_string($bytes) || $bytes === '') {
                return null;
            }
            $mime = $disk->mimeType($path);
            $mimeType = is_string($mime) && $mime !== '' ? $mime : 'image/jpeg';

            return $this->optimizeImagePayload(['mime_type' => $mimeType, 'bytes' => $bytes]);
        }

        if (file_exists($path)) {
            $bytes = file_get_contents($path);
            if (! is_string($bytes) || $bytes === '') {
                return null;
            }
            $mime = mime_content_type($path);
            $mimeType = is_string($mime) && $mime !== '' ? $mime : 'image/jpeg';

            return $this->optimizeImagePayload(['mime_type' => $mimeType, 'bytes' => $bytes]);
        }

        return null;
    }

    /**
     * Downscale high-resolution images to a maximum dimension of 1600px
     * and compress to JPEG to reduce network payload size and Gemini latency.
     *
     * @param  array{mime_type: string, bytes: string}  $imageData
     * @return array{mime_type: string, bytes: string}
     */
    protected function optimizeImagePayload(array $imageData): array
    {
        // If image is already 512KB or smaller, no compression is needed
        if (strlen($imageData['bytes']) <= 512 * 1024) {
            return $imageData;
        }

        if (! extension_loaded('gd')) {
            return $imageData;
        }

        try {
            $srcImage = @imagecreatefromstring($imageData['bytes']);
            if (! $srcImage instanceof \GdImage) {
                return $imageData;
            }

            $origWidth = imagesx($srcImage);
            $origHeight = imagesy($srcImage);

            if ($origWidth <= 0 || $origHeight <= 0) {
                return $imageData;
            }

            $maxDimension = 1600;
            $maxOriginal = max($origWidth, $origHeight);

            // If dimensions are already within limit and under 1MB, keep as is
            if ($maxOriginal <= $maxDimension && strlen($imageData['bytes']) <= 1024 * 1024) {
                return $imageData;
            }

            $scale = min(1.0, $maxDimension / $maxOriginal);
            $newWidth = (int) round($origWidth * $scale);
            $newHeight = (int) round($origHeight * $scale);

            $dstImage = imagecreatetruecolor($newWidth, $newHeight);
            if (! $dstImage instanceof \GdImage) {
                return $imageData;
            }

            // Fill background with white for transparent images (e.g. PNG)
            $white = imagecolorallocate($dstImage, 255, 255, 255);
            if (is_int($white)) {
                imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $white);
            }

            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

            ob_start();
            imagejpeg($dstImage, null, 85);
            $optimizedBytes = ob_get_clean();

            imagedestroy($srcImage);
            imagedestroy($dstImage);

            if (is_string($optimizedBytes) && strlen($optimizedBytes) > 0 && strlen($optimizedBytes) < strlen($imageData['bytes'])) {
                return [
                    'mime_type' => 'image/jpeg',
                    'bytes' => $optimizedBytes,
                ];
            }
        } catch (\Throwable $e) {
            Log::debug('Image optimization skipped: '.$e->getMessage());
        }

        return $imageData;
    }

    private function normalizeAccount(mixed $account): ?string
    {
        if (! is_string($account) && ! is_numeric($account)) {
            return null;
        }

        $normalizedAccount = trim((string) preg_replace('/^(?:account|a\/c|acc|ac)\s*[:#-]?\s*/i', '', trim((string) $account)));

        return $normalizedAccount !== '' ? $normalizedAccount : null;
    }

    /**
     * @return array{
     *     provider: null,
     *     trx_id: null,
     *     amount: null,
     *     fee: null,
     *     total_amount: null,
     *     balance: null,
     *     txn_type: null,
     *     beneficiary_bank: null,
     *     sender_account: null,
     *     sender_number: null,
     *     sender_name: null,
     *     receiver_account: null,
     *     receiver_number: null,
     *     receiver_name: null,
     *     reference: null,
     *     transaction_time: null,
     *     status_in_screenshot: string,
     *     is_suspicious_or_edited: bool,
     *     confidence_score: float,
     *     notes: string
     * }
     */
    private function fallbackResponse(string $reason): array
    {
        return [
            'provider' => null,
            'trx_id' => null,
            'amount' => null,
            'fee' => null,
            'total_amount' => null,
            'balance' => null,
            'txn_type' => null,
            'beneficiary_bank' => null,
            'sender_account' => null,
            'sender_number' => null,
            'sender_name' => null,
            'receiver_account' => null,
            'receiver_number' => null,
            'receiver_name' => null,
            'reference' => null,
            'transaction_time' => null,
            'status_in_screenshot' => 'unknown',
            'is_suspicious_or_edited' => false,
            'confidence_score' => 0.0,
            'notes' => $reason,
        ];
    }

    /**
     * Reconcile an uploaded payment screenshot against incoming SMS ground-truth candidates.
     *
     * @param  array<int, \App\Models\Transaction>  $candidateTransactions
     * @return array{
     *     matched_transaction_id: ?int,
     *     confidence_score: float,
     *     match_reason: string,
     *     is_suspicious_or_edited: bool,
     *     extracted_data: array<string, mixed>
     * }
     */
    public function matchScreenshotWithCandidateTransactions(
        string $screenshotPath,
        \App\Models\Invoice $invoice,
        array $candidateTransactions,
        ?PaymentProvider $provider = null,
        ?array $extractedData = null,
    ): array {
        $extracted = $extractedData ?? $this->analyzeScreenshot($screenshotPath);

        if (! empty($extracted['is_suspicious_or_edited'])) {
            return [
                'matched_transaction_id' => null,
                'confidence_score' => 0.0,
                'match_reason' => 'Receipt flagged as suspicious or edited: '.($extracted['notes'] ?? 'Unknown reason'),
                'is_suspicious_or_edited' => true,
                'extracted_data' => $extracted,
            ];
        }

        if (empty($candidateTransactions)) {
            return [
                'matched_transaction_id' => null,
                'confidence_score' => 0.0,
                'match_reason' => 'No candidate SMS transactions available to match.',
                'is_suspicious_or_edited' => false,
                'extracted_data' => $extracted,
            ];
        }

        return $this->reconcileCandidatesWithGemini(
            screenshotData: $extracted,
            candidateTransactions: $candidateTransactions,
            invoice: $invoice,
            provider: $provider,
        );
    }

    /**
     * Call Gemini model to reconcile screenshot data and candidate transactions,
     * returning the highest-confidence matched transaction ID and score.
     *
     * @param  array<string, mixed>  $screenshotData
     * @param  array<int, Transaction>  $candidateTransactions
     * @return array{
     *     matched_transaction_id: ?int,
     *     confidence_score: float,
     *     match_reason: string,
     *     is_suspicious_or_edited: bool,
     *     extracted_data: array<string, mixed>
     * }
     */
    public function reconcileCandidatesWithGemini(
        array $screenshotData,
        array $candidateTransactions,
        Invoice $invoice,
        ?PaymentProvider $provider = null,
    ): array {
        $apiKey = config('payment.gemini.api_key');
        $model = config('payment.gemini.model', 'gemini-flash-lite-latest');
        $baseUrl = config('payment.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        if (empty($apiKey)) {
            Log::warning('GEMINI_API_KEY is not configured in .env. Skipping automated AI candidate reconciliation.');

            return [
                'matched_transaction_id' => null,
                'confidence_score' => 0.0,
                'match_reason' => 'Gemini API key is not configured.',
                'is_suspicious_or_edited' => false,
                'extracted_data' => $screenshotData,
            ];
        }

        $candidatesPayload = array_values(array_map(function (Transaction $tx): array {
            return [
                'id' => $tx->id,
                'provider' => $tx->provider,
                'amount' => (float) $tx->amount,
                'trxid' => $tx->trxid,
                'sender_account' => $tx->sender_account,
                'receiver_account' => $tx->receiver_account,
                'received_at' => $tx->received_at ? (string) $tx->received_at : null,
                'created_at' => $tx->created_at ? (string) $tx->created_at : null,
                'message' => $tx->message,
            ];
        }, $candidateTransactions));

        $prompt = $this->buildCandidateReconciliationPrompt(
            screenshotData: $screenshotData,
            candidates: $candidatesPayload,
            invoice: $invoice,
            provider: $provider,
        );

        try {
            $endpoint = "{$baseUrl}/models/{$model}:generateContent";

            $response = Http::withHeader('x-goog-api-key', $apiKey)
                ->connectTimeout(10)
                ->timeout(30)
                ->retry(2, 500, null, false)
                ->post($endpoint, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'temperature' => 0.1,
                        'max_output_tokens' => 1024,
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('Gemini candidate reconciliation API call failed', [
                    'status' => $response->status(),
                ]);

                return [
                    'matched_transaction_id' => null,
                    'confidence_score' => 0.0,
                    'match_reason' => 'Gemini API returned HTTP '.$response->status().'.',
                    'is_suspicious_or_edited' => false,
                    'extracted_data' => $screenshotData,
                ];
            }

            $responseData = $response->json();
            $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $decoded = json_decode($rawText, true);

            if (! is_array($decoded)) {
                Log::error('Failed to decode Gemini candidate reconciliation JSON', ['raw_text' => $rawText]);

                return [
                    'matched_transaction_id' => null,
                    'confidence_score' => 0.0,
                    'match_reason' => 'Invalid JSON returned by Gemini candidate reconciliation.',
                    'is_suspicious_or_edited' => false,
                    'extracted_data' => $screenshotData,
                ];
            }

            $isSuspicious = (bool) ($decoded['is_suspicious_or_edited'] ?? false);
            $rawMatchedId = $decoded['matched_transaction_id'] ?? $decoded['transaction_id'] ?? null;
            $confidenceScore = isset($decoded['confidence_score']) ? (float) $decoded['confidence_score'] : 0.0;
            $confidenceScore = max(0.0, min(1.0, round($confidenceScore, 2)));
            $matchReason = (string) ($decoded['match_reason'] ?? $decoded['reason'] ?? 'No explanation provided.');

            // Validate that the matched transaction ID genuinely exists in the candidate list
            $validCandidateIds = array_column($candidatesPayload, 'id');
            $matchedTxId = null;

            if ($rawMatchedId !== null && in_array((int) $rawMatchedId, $validCandidateIds, true)) {
                $matchedTxId = (int) $rawMatchedId;
            }

            $threshold = (float) config('payment.confidence_threshold', 0.85);

            return [
                'matched_transaction_id' => ($matchedTxId !== null && $confidenceScore >= $threshold && ! $isSuspicious) ? $matchedTxId : null,
                'confidence_score' => $confidenceScore,
                'match_reason' => $matchReason,
                'is_suspicious_or_edited' => $isSuspicious,
                'extracted_data' => $screenshotData,
            ];
        } catch (\Throwable $e) {
            Log::error('Gemini candidate reconciliation exception', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return [
                'matched_transaction_id' => null,
                'confidence_score' => 0.0,
                'match_reason' => 'Gemini candidate reconciliation request failed before a response was received.',
                'is_suspicious_or_edited' => false,
                'extracted_data' => $screenshotData,
            ];
        }
    }

    /**
     * Build the prompt for Gemini to reconcile screenshot data with candidate transactions.
     *
     * @param  array<string, mixed>  $screenshotData
     * @param  array<int, array<string, mixed>>  $candidates
     */
    public function buildCandidateReconciliationPrompt(
        array $screenshotData,
        array $candidates,
        Invoice $invoice,
        ?PaymentProvider $provider = null,
    ): string {
        $rawProvider = $screenshotData['provider'] ?? null;
        $sanitizedScreenshot = [
            'provider' => $rawProvider instanceof PaymentProvider
                ? $rawProvider->getId()
                : $rawProvider,
            'trx_id' => $screenshotData['trx_id'] ?? null,
            'amount' => $screenshotData['amount'] ?? null,
            'fee' => $screenshotData['fee'] ?? null,
            'total_amount' => $screenshotData['total_amount'] ?? null,
            'balance' => $screenshotData['balance'] ?? null,
            'txn_type' => $screenshotData['txn_type'] ?? null,
            'beneficiary_bank' => $screenshotData['beneficiary_bank'] ?? null,
            'sender_account' => $screenshotData['sender_account'] ?? null,
            'sender_number' => $screenshotData['sender_number'] ?? null,
            'sender_name' => $screenshotData['sender_name'] ?? null,
            'receiver_account' => $screenshotData['receiver_account'] ?? null,
            'receiver_number' => $screenshotData['receiver_number'] ?? null,
            'receiver_name' => $screenshotData['receiver_name'] ?? null,
            'reference' => $screenshotData['reference'] ?? null,
            'transaction_time' => $screenshotData['transaction_time'] ?? null,
            'status_in_screenshot' => $screenshotData['status_in_screenshot'] ?? null,
            'notes' => $screenshotData['notes'] ?? null,
        ];

        $invoiceDetails = [
            'invoice_id' => $invoice->invoice_id,
            'reference' => $invoice->reference,
            'expected_amount' => (float) $invoice->amount,
            'currency' => $invoice->currency ?? 'BDT',
            'selected_provider' => $provider?->getId(),
        ];

        $screenshotJson = json_encode($sanitizedScreenshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $candidatesJson = json_encode($candidates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $invoiceJson = json_encode($invoiceDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are an expert financial reconciliation auditor.
Your job is to match a customer's payment proof (extracted screenshot data) against incoming unclaimed bank/MFS transactions received via SMS/API notifications.

=== EXPECTED INVOICE DETAILS ===
{$invoiceJson}

=== PAYMENT SCREENSHOT EXTRACTED DATA ===
{$screenshotJson}

=== CANDIDATE UNCLAIMED TRANSACTIONS ===
{$candidatesJson}

=== MATCHING RULES ===
1. Mandatory Amount Match: The candidate transaction's amount must match the transfer amount from the screenshot or the expected invoice amount (small variations <= 1.0 BDT due to rounding or fee inclusion are acceptable, but differing amounts must not match).
2. Transaction ID / Reference Match: Check if the screenshot's trx_id or reference appears in the candidate's trxid field or inside the SMS message text.
   - For bank transfers, the reference number on the customer receipt often matches the TrxID or appears in the bank SMS body.
3. Account Number / Phone Matching: Compare sender and receiver accounts. In Bangladesh receipts and SMS, accounts are often partially masked (e.g. "1503XXXXXX001" vs "1503***001", or phone numbers like "01624093099"). Match non-masked prefix and suffix digits.
4. Timestamp Match: Check that the transaction_time from the screenshot is temporally close to the candidate's received_at or created_at (typically within 30-60 minutes on the same day).
5. Fraud & Suspicion Detection: If the receipt details appear forged, contradictory, or fail to correspond to genuine financial records, flag is_suspicious_or_edited as true.

Identify the candidate transaction that has the HIGHEST confidence of matching this payment.
If multiple candidates match, pick the one with the highest confidence score.
If no candidate transaction matches the receipt, return matched_transaction_id as null and confidence_score as 0.0.

Return a STRICT JSON object with these exact keys:
- "matched_transaction_id": integer (the "id" of the best matching candidate transaction, or null if none match)
- "confidence_score": float between 0.0 and 1.0 (e.g. 0.95 for strong match with exact TrxID and amount; 0.0 if no match)
- "match_reason": string (a concise explanation of why this transaction matched or why no match was found)
- "is_suspicious_or_edited": boolean (true if the receipt appears altered, fake, or fraudulent; false otherwise)
PROMPT;
    }
}
