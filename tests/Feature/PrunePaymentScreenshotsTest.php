<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;

it('prunes expired screenshots according to tiered retention policy and preserves recent ones', function () {
    Storage::fake('public');

    // 1. Expired paid invoice (older than 14 days)
    $oldPaidFile = 'screenshots/old_paid.jpg';
    Storage::disk('public')->put($oldPaidFile, 'image_data');
    $oldPaidInvoice = Invoice::factory()->create([
        'status' => InvoiceStatus::PAID,
        'metadata' => [
            'verification_screenshot' => $oldPaidFile,
        ],
        'updated_at' => now()->subDays(15),
    ]);

    // 2. Recent paid invoice (within 14 days - should be PRESERVED)
    $recentPaidFile = 'screenshots/recent_paid.jpg';
    Storage::disk('public')->put($recentPaidFile, 'image_data');
    $recentPaidInvoice = Invoice::factory()->create([
        'status' => InvoiceStatus::PAID,
        'metadata' => [
            'verification_screenshot' => $recentPaidFile,
        ],
        'updated_at' => now()->subDays(3),
    ]);

    // 3. Expired failed invoice (older than 48 hours)
    $oldFailedFile = 'screenshots/old_failed.jpg';
    Storage::disk('public')->put($oldFailedFile, 'image_data');
    $oldFailedInvoice = Invoice::factory()->create([
        'status' => InvoiceStatus::PENDING,
        'metadata' => [
            'verification_status' => 'failed',
            'verification_screenshot' => $oldFailedFile,
        ],
        'created_at' => now()->subHours(50),
    ]);

    // 4. Recent failed invoice (within 48 hours - should be PRESERVED for support inspection)
    $recentFailedFile = 'screenshots/recent_failed.jpg';
    Storage::disk('public')->put($recentFailedFile, 'image_data');
    $recentFailedInvoice = Invoice::factory()->create([
        'status' => InvoiceStatus::PENDING,
        'metadata' => [
            'verification_status' => 'failed',
            'verification_screenshot' => $recentFailedFile,
        ],
        'created_at' => now()->subHours(6),
    ]);

    // Run the pruning command
    $this->artisan('payments:prune-screenshots')
        ->expectsOutput('Successfully pruned 2 expired screenshot(s).')
        ->assertSuccessful();

    // Verify disk states
    Storage::disk('public')->assertMissing($oldPaidFile);
    Storage::disk('public')->assertMissing($oldFailedFile);
    Storage::disk('public')->assertExists($recentPaidFile);
    Storage::disk('public')->assertExists($recentFailedFile);

    // Verify invoice metadata updates
    $oldPaidInvoice->refresh();
    expect($oldPaidInvoice->metadata['verification_screenshot'] ?? null)->toBeNull()
        ->and($oldPaidInvoice->metadata['verification_screenshot_pruned_at'] ?? null)->not->toBeNull();

    $oldFailedInvoice->refresh();
    expect($oldFailedInvoice->metadata['verification_screenshot'] ?? null)->toBeNull()
        ->and($oldFailedInvoice->metadata['verification_screenshot_pruned_at'] ?? null)->not->toBeNull();

    $recentPaidInvoice->refresh();
    expect($recentPaidInvoice->metadata['verification_screenshot'])->toBe($recentPaidFile);

    $recentFailedInvoice->refresh();
    expect($recentFailedInvoice->metadata['verification_screenshot'])->toBe($recentFailedFile);
});

