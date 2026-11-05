<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Payment\PaymentManager;
use App\Payment\PaymentProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentVerificationService
{
    public function __construct(
        protected GeminiVisionService $geminiVision,
        protected PaymentManager $paymentManager,
    ) {}

    /**
     * Find a matching unclaimed transaction by its Transaction ID or SMS reference within the configured window.
     */
    public function findMatchingTransactionByTrxId(
        Invoice $invoice,
        PaymentProvider|string $provider,
        string $trxId,
    ): ?Transaction {
        $windowMinutes = (int) config('payment.window_time_minutes', 60);
        $providerVariants = $this->resolveProviderVariants($provider);
        $cleanTrxId = strtoupper(trim($trxId));

        return Transaction::query()
            ->whereIn('provider', $providerVariants)
            ->where('amount', $invoice->amount)
            ->whereNull('invoice_id')
            ->where(function ($q) use ($cleanTrxId) {
                $q->where('trxid', $cleanTrxId)
                    ->orWhere('message', 'like', "%{$cleanTrxId}%");
            })
            ->where(function ($q) use ($windowMinutes) {
                $cutoff = now()->subMinutes($windowMinutes);
                $q->where('received_at', '>=', $cutoff)
                    ->orWhere(function ($sub) use ($cutoff) {
                        $sub->whereNull('received_at')
                            ->where('created_at', '>=', $cutoff);
                    });
            })
            ->lockForUpdate()
            ->first();
    }

    /**
     * Fetch recent unclaimed incoming SMS transactions for AI verification.
     *
     * @return Collection<int, Transaction>
     */
    public function getUnclaimedCandidates(
        Invoice $invoice,
        PaymentProvider|string $provider,
        int $limit = 10,
    ): Collection {
        $windowMinutes = (int) config('payment.window_time_minutes', 60);
        $providerVariants = $this->resolveProviderVariants($provider);

        return Transaction::query()
            ->whereIn('provider', $providerVariants)
            ->whereNull('invoice_id')
            ->where(function ($query) use ($invoice) {
                $query->where('amount', $invoice->amount)
                    ->orWhereRaw('ABS(amount - ?) <= 1.0', [$invoice->amount]);
            })
            ->where(function ($query) use ($windowMinutes) {
                $cutoff = now()->subMinutes($windowMinutes);
                $query->where('received_at', '>=', $cutoff)
                    ->orWhere(function ($sub) use ($cutoff) {
                        $sub->whereNull('received_at')
                            ->where('created_at', '>=', $cutoff);
                    });
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Atomically claim a transaction for an invoice, update invoice status, and dispatch webhook.
     * SMS fields on the transaction are strictly preserved as ground truth.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function claimTransaction(
        Invoice $invoice,
        Transaction $transaction,
        array $metadata = [],
    ): bool {
        return DB::transaction(function () use ($invoice, $transaction, $metadata): bool {
            $claimed = Transaction::query()
                ->where('id', $transaction->id)
                ->whereNull('invoice_id')
                ->update([
                    'invoice_id' => $invoice->id,
                    'status' => 'approved',
                ]);

            if (! $claimed) {
                return false;
            }

            $mergedMetadata = array_merge($invoice->metadata ?? [], $metadata);
            if (isset($mergedMetadata['verification_ai']['provider']) && $mergedMetadata['verification_ai']['provider'] instanceof PaymentProvider) {
                $mergedMetadata['verification_ai']['provider'] = $mergedMetadata['verification_ai']['provider']->getId();
            }

            $invoice->update([
                'status' => InvoiceStatus::PAID,
                'metadata' => $mergedMetadata,
            ]);

            $this->postWebhook($invoice, $transaction->trxid);

            return true;
        });
    }

    /**
     * Dispatches the webhook notification to the invoice's webhook URL.
     */
    public function postWebhook(Invoice $invoice, ?string $trxId = null): void
    {
        if (! $invoice->webhook_url) {
            return;
        }

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->post($invoice->webhook_url, [
                    'invoice_id' => $invoice->invoice_id,
                    'trxid' => $trxId ?? $invoice->transaction?->trxid,
                    'ulid' => $invoice->ulid,
                    'amount' => $invoice->amount,
                    'currency' => $invoice->currency,
                    'status' => $invoice->status->value,
                    'client_name' => $invoice->client_name,
                    'client_email' => $invoice->client_email,
                    'client_phone' => $invoice->client_phone,
                    'paid_at' => now()->toDateTimeString(),
                    'metadata' => $invoice->metadata,
                ]);

            if ($response->failed()) {
                Log::error('Webhook delivery failed', [
                    'invoice_id' => $invoice->id,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Webhook delivery exception', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolves all provider aliases dynamically via PaymentManager.
     *
     * @return array<string>
     */
    public function resolveProviderVariants(
        PaymentProvider|string $provider,
        PaymentProvider|string|null $extractedProvider = null,
    ): array {
        $providers = array_filter([$provider, $extractedProvider]);
        $variants = [];

        foreach ($providers as $p) {
            $variants = array_merge($variants, $this->paymentManager->getProviderVariants($p));
        }

        return array_values(array_unique(array_filter($variants)));
    }

    /**
     * Verify an invoice against a transaction ID or screenshot (used by sync callers and feature tests).
     *
     * @return array{
     *     success: bool,
     *     transaction: ?Transaction,
     *     error: ?string,
     *     provider: ?PaymentProvider,
     *     extracted_data: ?array
     * }
     */
    public function verify(
        Invoice $invoice,
        PaymentProvider|string $provider,
        ?string $transactionId = null,
        ?string $screenshotPath = null,
    ): array {
        $resolvedProvider = $this->paymentManager->findProvider($provider);

        if (! empty($transactionId)) {
            $matched = $this->findMatchingTransactionByTrxId($invoice, $resolvedProvider ?? $provider, $transactionId);
            if ($matched) {
                return [
                    'success' => true,
                    'transaction' => $matched,
                    'error' => null,
                    'provider' => $resolvedProvider,
                    'extracted_data' => null,
                ];
            }
        }

        if ($screenshotPath !== null) {
            $candidates = $this->getUnclaimedCandidates($invoice, $resolvedProvider ?? $provider);
            $aiResult = $this->geminiVision->matchScreenshotWithCandidateTransactions(
                screenshotPath: $screenshotPath,
                invoice: $invoice,
                candidateTransactions: $candidates->all(),
                provider: $resolvedProvider ?? $provider,
            );

            if (! empty($aiResult['is_suspicious_or_edited'])) {
                return [
                    'success' => false,
                    'transaction' => null,
                    'error' => 'The uploaded receipt appears to be edited or suspicious.',
                    'provider' => $resolvedProvider,
                    'extracted_data' => $aiResult['extracted_data'] ?? null,
                ];
            }

            $matchedId = $aiResult['matched_transaction_id'] ?? null;
            $candidate = $matchedId ? $candidates->firstWhere('id', $matchedId) : null;

            if ($candidate) {
                return [
                    'success' => true,
                    'transaction' => $candidate,
                    'error' => null,
                    'provider' => $resolvedProvider,
                    'extracted_data' => $aiResult['extracted_data'] ?? null,
                ];
            }
        }

        return [
            'success' => false,
            'transaction' => null,
            'error' => 'No matching transaction was found.',
            'provider' => $resolvedProvider,
            'extracted_data' => null,
        ];
    }
}
