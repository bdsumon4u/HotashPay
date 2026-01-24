<?php

use App\Filament\Resources\Transactions\Pages\CreateTransaction;
use App\Filament\Resources\Transactions\Pages\EditTransaction;
use App\Models\Transaction;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::factory()->create());
});

it('creates transaction with provided data', function () {
    $smsMessage = 'Payment received: Tk. 1,500.00 from 01712345678.';

    \Livewire\Livewire::test(CreateTransaction::class)
        ->fillForm([
            'sim' => 'bKash',
            'message' => $smsMessage,
            'entry_type' => 'sms',
        ])
        ->call('create')
        ->assertNotified();

    $transaction = Transaction::latest()->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->sim)->toBe('bKash')
        ->and($transaction->message)->toBe($smsMessage)
        ->and($transaction->entry_type)->toBe('sms');
});

it('updates transaction with new data', function () {
    $transaction = Transaction::factory()->create([
        'sim' => 'OldProvider',
        'message' => 'Old message',
        'amount' => 100,
    ]);

    $newSmsMessage = 'Payment received: Tk. 2,500.00 from 01798765432.';

    \Livewire\Livewire::test(EditTransaction::class, ['record' => $transaction->id])
        ->fillForm([
            'sim' => 'Nagad',
            'message' => $newSmsMessage,
        ])
        ->call('save')
        ->assertNotified();

    $transaction->refresh();

    expect($transaction->sim)->toBe('Nagad')
        ->and($transaction->message)->toBe($newSmsMessage);
});
