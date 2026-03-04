<?php

use App\Enums\InvoiceStatus;
use App\Filament\Pages\MakePayment;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('verifies transaction and marks invoice as paid with database transaction', function () {
    Http::fake();

    // Create an unpaid invoice
    $invoice = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
        'webhook_url' => 'https://example.com/webhook',
    ]);

    // Create an unclaimed transaction with matching details
    $transaction = Transaction::factory()->create([
        'provider' => 'bKash',
        'trxid' => 'ABC123XYZ',
        'amount' => 1500,
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction');

    // Verify transaction is now claimed
    $transaction->refresh();
    expect($transaction->invoice_id)->toBe($invoice->id);

    // Verify invoice is marked as paid
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PAID);
});

it('fails verification when transaction id does not match', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
    ]);

    Transaction::factory()->create([
        'provider' => 'bKash',
        'trxid' => 'ABC123XYZ',
        'amount' => 1500,
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'WRONG_ID')
        ->call('verifyTransaction');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PENDING);
});

it('fails verification when amount does not match', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
    ]);

    $transaction = Transaction::factory()->create([
        'provider' => 'bKash',
        'trxid' => 'ABC123XYZ',
        'amount' => 2000, // Different amount
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PENDING);

    $transaction->refresh();
    expect($transaction->invoice_id)->toBeNull();
});

it('fails verification when transaction is already claimed', function () {
    $invoice1 = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PAID,
    ]);

    $invoice2 = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
    ]);

    // Transaction already claimed by invoice1
    Transaction::factory()->create([
        'provider' => 'bKash',
        'trxid' => 'ABC123XYZ',
        'amount' => 1500,
        'invoice_id' => $invoice1->id,
        'status' => 'approved',
    ]);

    Livewire::test(MakePayment::class, ['invoice' => $invoice2])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction');

    $invoice2->refresh();
    expect($invoice2->status)->toBe(InvoiceStatus::PENDING);
});

it('fails verification when provider does not match', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
    ]);

    $transaction = Transaction::factory()->create([
        'provider' => 'Nagad',
        'trxid' => 'ABC123XYZ',
        'amount' => 1500,
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bKash') // Different provider
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PENDING);

    $transaction->refresh();
    expect($transaction->invoice_id)->toBeNull();
});

it('uses database transaction for payment verification', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
    ]);

    Transaction::factory()->create([
        'provider' => 'bKash',
        'trxid' => 'ABC123XYZ',
        'amount' => 1500,
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    // Verify changes are committed to database
    $initialStatus = $invoice->status;

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction');

    $invoice->refresh();

    // Invoice status should have changed from pending to paid
    expect($invoice->status)->not->toBe($initialStatus)
        ->and($invoice->status)->toBe(InvoiceStatus::PAID);
});
