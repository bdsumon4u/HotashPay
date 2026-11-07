<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PrunePaymentScreenshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:prune-screenshots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune expired payment verification screenshots from storage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $disk = Storage::disk('public');
        $prunedCount = 0;

        $verifiedCutoff = now()->subDays((int) config('payment.screenshot_retention_days', 14));
        $failedCutoff = now()->subHours((int) config('payment.failed_screenshot_retention_hours', 48));

        // 1. Prune verified receipts older than retention days
        $verifiedInvoices = Invoice::query()
            ->where('status', InvoiceStatus::PAID)
            ->where('updated_at', '<=', $verifiedCutoff)
            ->whereNotNull('metadata->verification_screenshot')
            ->get();

        foreach ($verifiedInvoices as $invoice) {
            $path = $invoice->metadata['verification_screenshot'] ?? null;
            if ($path && $disk->exists($path)) {
                $disk->delete($path);
            }

            $metadata = $invoice->metadata ?? [];
            unset($metadata['verification_screenshot']);
            $metadata['verification_screenshot_pruned_at'] = now()->toIso8601String();

            $invoice->update(['metadata' => $metadata]);
            $prunedCount++;
        }

        // 2. Prune failed or timed-out receipts older than retention hours
        $failedInvoices = Invoice::query()
            ->where('status', '!=', InvoiceStatus::PAID)
            ->where('created_at', '<=', $failedCutoff)
            ->whereNotNull('metadata->verification_screenshot')
            ->get();

        foreach ($failedInvoices as $invoice) {
            $path = $invoice->metadata['verification_screenshot'] ?? null;
            if ($path && $disk->exists($path)) {
                $disk->delete($path);
            }

            $metadata = $invoice->metadata ?? [];
            unset($metadata['verification_screenshot']);
            $metadata['verification_screenshot_pruned_at'] = now()->toIso8601String();

            $invoice->update(['metadata' => $metadata]);
            $prunedCount++;
        }

        $this->info("Successfully pruned {$prunedCount} expired screenshot(s).");

        return self::SUCCESS;
    }
}

