<?php

use App\Enums\InvoiceStatus;
use App\Filament\Pages\MakePayment;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;

it('verifies transaction and marks invoice as paid', function () {
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

    \Livewire\Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction')
        ->assertNotified();

    // Verify transaction is now claimed
    $transaction->refresh();
    expect($transaction->invoice_id)->toBe($invoice->id);

    // Verify invoice is marked as paid
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PAID);

    // Verify webhook was called
    Http::assertSent(function ($request) use ($invoice) {
        return $request->url() === 'https://example.com/webhook' &&
               $request['invoice_id'] === $invoice->id &&
               $request['status'] === 'paid' &&
               $request['amount'] === 1500;
    });
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

    \Livewire\Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'WRONG_ID')
        ->call('verifyTransaction')
        ->assertNotified();

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PENDING);
});

it('fails verification when amount does not match', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
    ]);

    Transaction::factory()->create([
        'provider' => 'bKash',
        'trxid' => 'ABC123XYZ',
        'amount' => 2000, // Different amount
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    \Livewire\Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction')
        ->assertNotified();

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PENDING);
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
    $transaction = Transaction::factory()->create([
        'provider' => 'bKash',
        'trxid' => 'ABC123XYZ',
        'amount' => 1500,
        'invoice_id' => $invoice1->id,
        'status' => 'approved',
    ]);

    \Livewire\Livewire::test(MakePayment::class, ['invoice' => $invoice2])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction')
        ->assertNotified();

    $invoice2->refresh();
    expect($invoice2->status)->toBe(InvoiceStatus::PENDING);
});

it('fails verification when provider does not match', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
    ]);

    Transaction::factory()->create([
        'provider' => 'Nagad',
        'trxid' => 'ABC123XYZ',
        'amount' => 1500,
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    \Livewire\Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bKash') // Different provider
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction')
        ->assertNotified();

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PENDING);
});

it('redirects to success url when already paid', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::PAID,
        'redirect_url' => 'https://example.com/success',
    ]);

    \Livewire\Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->assertRedirect('https://example.com/success');
});
