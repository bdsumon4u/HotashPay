<?php

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
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
    ): void {
        $invoice = Invoice::query()->find($this->invoiceId);
        if (! $invoice || $invoice->status === InvoiceStatus::PAID) {
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

        // 1. Fetch unclaimed candidate transactions from incoming SMS
        $candidates = $verifier->getUnclaimedCandidates($invoice, $this->provider);

        // 2. Reconcile screenshot against candidate transactions via Gemini AI
        $matchResult = $geminiVision->matchScreenshotWithCandidateTransactions(
            screenshotPath: $this->screenshotPath,
            invoice: $invoice,
            candidateTransactions: $candidates->all(),
            provider: $this->provider,
        );

        // Flag suspicious or edited screenshots immediately
        if (! empty($matchResult['is_suspicious_or_edited'])) {
            $metadata = $invoice->metadata ?? [];
            $metadata['verification_status'] = 'failed';
            $metadata['verification_error'] = 'The uploaded receipt appears to be edited or suspicious. Please upload an unaltered receipt.';
            $metadata['verification_ai'] = $matchResult['extracted_data'] ?? [];
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
                    'verification_ai' => $matchResult['extracted_data'] ?? [],
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
