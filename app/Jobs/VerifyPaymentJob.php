<?php

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Payment\PaymentManager;
use App\Services\GeminiVisionService;
use App\Services\PaymentVerificationService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class VerifyPaymentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 25;

    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $invoiceId,
        public string $provider,
        public string $screenshotPath,
        public ?string $transactionId = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        GeminiVisionService $geminiVision,
        PaymentVerificationService $verifier,
        ?PaymentManager $paymentManager = null,
    ): void {
        $paymentManager ??= app(PaymentManager::class);
        $paymentProvider = $paymentManager->findProvider($this->provider);

        $invoice = Invoice::query()->find($this->invoiceId);
        if (! $invoice || $invoice->status === InvoiceStatus::PAID) {
            return;
        }

        if (($invoice->metadata['verification_status'] ?? null) === 'cancelled') {
            return;
        }

        $startedAt = isset($invoice->metadata['verification_started_at'])
            ? Carbon::parse($invoice->metadata['verification_started_at'])
            : $invoice->created_at;

        $timeoutMinutes = (int) config('payment.timeout_minutes', 10);

        if ($startedAt && $startedAt->diffInMinutes(now()) >= $timeoutMinutes) {
            $metadata = $invoice->metadata ?? [];
            $metadata['verification_status'] = 'failed';
            $metadata['verification_error'] = 'Verification timed out after 10 minutes without matching transaction confirmation.';
            $invoice->update(['metadata' => $metadata]);

            return;
        }

        // Fast-path 1: Check instant match by user-submitted Transaction ID if provided
        if (! empty($this->transactionId)) {
            $matchedByTrx = $verifier->findMatchingTransactionByTrxId(
                $invoice,
                $paymentProvider ?? $this->provider,
                $this->transactionId
            );

            if ($matchedByTrx) {
                $verifier->claimTransaction($invoice, $matchedByTrx, [
                    'verification_status' => 'completed',
                    'verification_confidence' => 1.0,
                    'verification_reason' => "Exact match for user-submitted Transaction ID '{$this->transactionId}'",
                ]);

                return;
            }
        }

        // 1. Fetch unclaimed candidate transactions from incoming SMS
        $candidates = $verifier->getUnclaimedCandidates($invoice, $paymentProvider ?? $this->provider);

        // 2. Resolve and cache screenshot OCR data (tied to specific screenshotPath)
        $cachedScreenshot = $invoice->metadata['verification_cached_screenshot'] ?? null;
        $cachedAi = ($cachedScreenshot === $this->screenshotPath) ? ($invoice->metadata['verification_ai'] ?? null) : null;

        if (! is_array($cachedAi) || empty($cachedAi)) {
            $extracted = $geminiVision->analyzeScreenshot($this->screenshotPath);
            $metadata = $invoice->metadata ?? [];
            $metadata['verification_ai'] = $extracted;
            $metadata['verification_cached_screenshot'] = $this->screenshotPath;
            unset($metadata['verification_last_candidates']); // New screenshot -> reset candidate evaluation history
            $invoice->update(['metadata' => $metadata]);
        } else {
            $extracted = $cachedAi;
        }

        // Flag suspicious or edited screenshots immediately
        if (! empty($extracted['is_suspicious_or_edited'])) {
            $metadata = $invoice->metadata ?? [];
            $metadata['verification_status'] = 'failed';
            $metadata['verification_error'] = 'The uploaded receipt appears to be edited or suspicious. Please upload an unaltered receipt.';
            $metadata['verification_ai'] = $extracted;
            $invoice->update(['metadata' => $metadata]);

            return;
        }

        // Fast-path 2: If screenshot contained an exact TrxID, check instant match against DB
        $extractedTrxId = ! empty($extracted['trx_id']) ? (string) $extracted['trx_id'] : null;
        if ($extractedTrxId !== null) {
            $matchedByExtractedTrx = $verifier->findMatchingTransactionByTrxId(
                $invoice,
                $paymentProvider ?? $this->provider,
                $extractedTrxId
            );

            if ($matchedByExtractedTrx) {
                $verifier->claimTransaction($invoice, $matchedByExtractedTrx, [
                    'verification_status' => 'completed',
                    'verification_confidence' => 0.99,
                    'verification_reason' => "Exact match for extracted TrxID '{$extractedTrxId}' from receipt",
                    'verification_ai' => $extracted,
                ]);

                return;
            }
        }

        // 3. If no candidate transactions exist yet, release back to queue without burning AI tokens
        if ($candidates->isEmpty()) {
            if ($startedAt && $startedAt->diffInMinutes(now()) < $timeoutMinutes) {
                $this->release(20);

                return;
            }

            $metadata = $invoice->metadata ?? [];
            $metadata['verification_status'] = 'failed';
            $metadata['verification_error'] = 'Could not match payment confirmation within 10 minutes. Please contact support.';
            $invoice->update(['metadata' => $metadata]);

            return;
        }

        // 4. Candidate signature check: Avoid re-running Gemini if the candidates haven't changed
        $candidateIds = $candidates->pluck('id')->sort()->values()->all();
        $candidateSignature = implode(',', $candidateIds);
        $lastEvaluatedSignature = $invoice->metadata['verification_last_candidates'] ?? null;

        if ($lastEvaluatedSignature === $candidateSignature) {
            // Unclaimed candidates remain the same as last evaluation.
            // Release and wait for new incoming SMS without burning AI tokens.
            if ($startedAt && $startedAt->diffInMinutes(now()) < $timeoutMinutes) {
                $this->release(20);

                return;
            }

            $metadata = $invoice->metadata ?? [];
            $metadata['verification_status'] = 'failed';
            $metadata['verification_error'] = 'Could not match payment confirmation within 10 minutes. Please contact support.';
            $invoice->update(['metadata' => $metadata]);

            return;
        }

        // 5. Reconcile screenshot against candidate transactions via Gemini AI
        $matchResult = $geminiVision->matchScreenshotWithCandidateTransactions(
            screenshotPath: $this->screenshotPath,
            invoice: $invoice,
            candidateTransactions: $candidates->all(),
            provider: $paymentProvider,
            extractedData: $extracted,
        );

        // Record the signature of the candidates we just evaluated to prevent duplicate runs
        $metadata = $invoice->metadata ?? [];
        $metadata['verification_last_candidates'] = $candidateSignature;
        $invoice->update(['metadata' => $metadata]);

        // Flag suspicious or edited screenshots if flagged during reconciliation
        if (! empty($matchResult['is_suspicious_or_edited'])) {
            $metadata = $invoice->metadata ?? [];
            $metadata['verification_status'] = 'failed';
            $metadata['verification_error'] = 'The uploaded receipt appears to be edited or suspicious. Please upload an unaltered receipt.';
            $metadata['verification_ai'] = $matchResult['extracted_data'] ?? $extracted;
            $invoice->update(['metadata' => $metadata]);

            return;
        }

        $threshold = (float) config('payment.confidence_threshold', 0.85);
        $matchedId = $matchResult['matched_transaction_id'] ?? null;
        $confidence = (float) ($matchResult['confidence_score'] ?? 0.0);

        if ($matchedId !== null && $confidence >= $threshold) {
            $candidateTx = $candidates->firstWhere('id', $matchedId);
            if ($candidateTx) {
                $verifier->claimTransaction($invoice, $candidateTx, [
                    'verification_status' => 'completed',
                    'verification_confidence' => $confidence,
                    'verification_reason' => $matchResult['match_reason'] ?? null,
                    'verification_ai' => $matchResult['extracted_data'] ?? $extracted,
                ]);

                return;
            }
        }

        // If no match found yet and still within the 10-minute window, release back to queue
        if ($startedAt && $startedAt->diffInMinutes(now()) < $timeoutMinutes) {
            $this->release(20);

            return;
        }

        // Exceeded 10 minutes without matching transaction
        $metadata = $invoice->metadata ?? [];
        $metadata['verification_status'] = 'failed';
        $metadata['verification_error'] = 'Could not match payment confirmation within 10 minutes. Please contact support.';
        $invoice->update(['metadata' => $metadata]);
    }
}
