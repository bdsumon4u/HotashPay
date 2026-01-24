<?php

namespace App\Filament\Pages;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Payment\PaymentManager;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MakePayment extends SimplePage
{
    protected bool $hasTopbar = false;

    protected string $view = 'filament.pages.make-payment';

    public Invoice $invoice;

    public array $gateways = [];

    public ?string $selectedProvider = null;

    public ?string $transactionId = null;

    public bool $isVerifying = false;

    public function mount(Invoice $invoice, PaymentManager $paymentManager): void
    {
        $this->invoice = $invoice;

        // Check if invoice is already paid
        if ($this->invoice->status === InvoiceStatus::PAID) {
            $this->redirect($this->invoice->redirect_url);

            return;
        }

        $this->gateways = [
            'mobile' => [
                'name' => 'Mobile Banking',
                'drivers' => $paymentManager->getEnabledDrivers('mobile'),
            ],
            'ibanking' => [
                'name' => 'Net Banking',
                'drivers' => $paymentManager->getEnabledDrivers('ibanking'),
            ],
            'international' => [
                'name' => 'International',
                'drivers' => $paymentManager->getEnabledDrivers('international'),
            ],
        ];
    }

    public function verifyTransaction(): void
    {
        $this->isVerifying = true;

        // Validate inputs
        if (! $this->selectedProvider || ! $this->transactionId) {
            Notification::make()
                ->danger()
                ->title('Validation Error')
                ->body('Please select a payment method and enter transaction ID.')
                ->send();

            $this->isVerifying = false;

            return;
        }

        // Find matching unclaimed transaction
        $transaction = Transaction::query()
            ->where('provider', $this->selectedProvider)
            ->where('trxid', $this->transactionId)
            ->where('amount', $this->invoice->amount)
            ->whereNull('invoice_id')
            ->where('status', 'approved')
            ->first();

        if (! $transaction) {
            Notification::make()
                ->danger()
                ->title('Transaction Not Found')
                ->body('No matching transaction found. Please verify your transaction ID and try again.')
                ->send();

            $this->isVerifying = false;

            return;
        }

        // Claim the transaction
        $transaction->update([
            'invoice_id' => $this->invoice->id,
        ]);

        // Mark invoice as paid
        $this->invoice->update([
            'status' => InvoiceStatus::PAID,
        ]);

        // Post webhook
        $this->postWebhook();

        // Show success notification
        Notification::make()
            ->success()
            ->title('Payment Verified')
            ->body('Your payment has been successfully verified!')
            ->send();

        // Redirect to success URL
        $this->redirect($this->invoice->redirect_url);
    }

    protected function postWebhook(): void
    {
        if (! $this->invoice->webhook_url) {
            return;
        }

        try {
            Http::timeout(10)->post($this->invoice->webhook_url, [
                'invoice_id' => $this->invoice->id,
                'ulid' => $this->invoice->ulid,
                'amount' => $this->invoice->amount,
                'currency' => $this->invoice->currency,
                'status' => $this->invoice->status->value,
                'client_name' => $this->invoice->client_name,
                'client_email' => $this->invoice->client_email,
                'client_phone' => $this->invoice->client_phone,
                'paid_at' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            // Log the error but don't fail the payment
            Log::error('Webhook posting failed', [
                'invoice_id' => $this->invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
