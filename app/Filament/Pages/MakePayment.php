<?php

namespace App\Filament\Pages;

use App\Enums\InvoiceStatus;
use App\Jobs\VerifyPaymentJob;
use App\Models\Invoice;
use App\Payment\PaymentManager;
use App\Services\PaymentVerificationService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class MakePayment extends SimplePage
{
    use WithFileUploads;

    protected bool $hasTopbar = false;

    protected string $view = 'filament.pages.make-payment';

    public Invoice $invoice;

    public array $gateways = [];

    public ?string $selectedProvider = null;

    public ?string $transactionId = null;

    /** @var TemporaryUploadedFile|null */
    public $screenshot = null;

    public bool $isBankPayment = false;

    public bool $isVerifying = false;

    public bool $verificationFailed = false;

    public ?string $verificationError = null;

    public function mount(Invoice $invoice, PaymentManager $paymentManager): void
    {
        $this->invoice = $invoice;

        // Check if invoice is already paid
        if ($this->invoice->status === InvoiceStatus::PAID) {
            $this->redirect($this->invoice->redirect_url);

            return;
        }

        $metadata = $this->invoice->metadata ?? [];
        $status = $metadata['verification_status'] ?? null;

        if ($status === 'processing') {
            $startedAt = isset($metadata['verification_started_at'])
                ? Carbon::parse($metadata['verification_started_at'])
                : null;
            $timeoutMinutes = (int) config('payment.timeout_minutes', 10);

            if ($startedAt && $startedAt->diffInMinutes(now()) < $timeoutMinutes) {
                $this->isVerifying = true;
                $this->selectedProvider = $metadata['verification_provider'] ?? null;
            } else {
                $this->verificationFailed = true;
                $this->verificationError = 'Verification timed out after 10 minutes. Please contact support.';
            }
        } elseif ($status === 'failed') {
            $this->verificationFailed = true;
            $this->verificationError = $metadata['verification_error'] ?? 'Payment verification failed. Please contact support.';
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

        $this->isBankPayment = $this->checkIsBankPayment($this->selectedProvider);
    }

    public function checkIsBankPayment(?string $provider): bool
    {
        if (! $provider) {
            return false;
        }

        $driver = app(PaymentManager::class)->getDriverMetadata($provider);

        return ($driver['type'] ?? '') === 'ibanking' || str_starts_with($provider, 'bank-');
    }

    public function isBankPayment(): bool
    {
        return $this->isBankPayment;
    }

    public function getIsBankPaymentProperty(): bool
    {
        return $this->isBankPayment();
    }

    public function selectProvider(?string $provider): void
    {
        $this->selectedProvider = $provider;
        $this->isBankPayment = $this->checkIsBankPayment($provider);
        $this->transactionId = null;
        $this->screenshot = null;
    }

    public function updatedSelectedProvider(?string $value): void
    {
        $this->isBankPayment = $this->checkIsBankPayment($value);
    }

    public function removeScreenshot(): void
    {
        $this->screenshot = null;
    }

    public function verifyTransaction(?PaymentVerificationService $verifier = null): void
    {
        $verifier ??= app(PaymentVerificationService::class);
        $this->isVerifying = true;
        $this->verificationFailed = false;
        $this->verificationError = null;

        // Validate provider selection
        if (! $this->selectedProvider) {
            Notification::make()
                ->danger()
                ->title('Payment Method Required')
                ->body('Please select a payment method.')
                ->send();

            $this->isVerifying = false;

            return;
        }

        // Validate Bank requirement: screenshot is required, no transaction ID
        if ($this->isBankPayment && ! $this->screenshot) {
            Notification::make()
                ->danger()
                ->title('Screenshot Required')
                ->body('Please upload your bank transfer receipt or confirmation screenshot.')
                ->send();

            $this->isVerifying = false;

            return;
        }

        // Validate MFS requirement: either transaction ID or screenshot is required
        if (! $this->isBankPayment && ! $this->transactionId && ! $this->screenshot) {
            Notification::make()
                ->danger()
                ->title('Verification Detail Required')
                ->body('Please enter your Transaction ID or upload a payment screenshot.')
                ->send();

            $this->isVerifying = false;

            return;
        }

        // Path A: User submitted manual Transaction ID without a screenshot
        if (! $this->screenshot && $this->transactionId) {
            $matched = $verifier->findMatchingTransactionByTrxId($this->invoice, $this->selectedProvider, $this->transactionId);

            if (! $matched) {
                Notification::make()
                    ->danger()
                    ->title('Transaction Not Found')
                    ->body("No matching transaction was found for Transaction ID '{$this->transactionId}'. Please verify the ID or wait 1-2 minutes if you just transferred.")
                    ->send();

                $this->isVerifying = false;

                return;
            }

            if (! $verifier->claimTransaction($this->invoice, $matched)) {
                Notification::make()
                    ->danger()
                    ->title('Already Claimed')
                    ->body('This transaction has already been claimed by another invoice.')
                    ->send();

                $this->isVerifying = false;

                return;
            }

            Notification::make()
                ->success()
                ->title('Payment Verified')
                ->body('Your payment has been successfully verified!')
                ->send();

            $this->redirect($this->invoice->redirect_url);

            return;
        }

        // Path B: User uploaded a screenshot (or screenshot + TrxID)
        $this->validate([
            'screenshot' => ['image', 'max:10240'], // 10MB max
        ]);

        $stored = $this->screenshot->store('screenshots', 'public');
        if ($stored === false) {
            Notification::make()
                ->danger()
                ->title('Upload Failed')
                ->body('Failed to save the uploaded screenshot. Please try again.')
                ->send();

            $this->isVerifying = false;

            return;
        }

        $metadata = $this->invoice->metadata ?? [];
        $metadata['verification_status'] = 'processing';
        $metadata['verification_started_at'] = now()->toIso8601String();
        $metadata['verification_screenshot'] = $stored;
        $metadata['verification_provider'] = $this->selectedProvider;
        if ($this->transactionId) {
            $metadata['verification_user_trxid'] = $this->transactionId;
        }

        $this->invoice->update([
            'metadata' => $metadata,
        ]);

        VerifyPaymentJob::dispatch(
            invoiceId: $this->invoice->id,
            provider: $this->selectedProvider,
            screenshotPath: $stored,
            transactionId: $this->transactionId,
        );

        // In synchronous queue environments (like PHPUnit testing), job executes immediately
        $this->invoice->refresh();

        if ($this->invoice->status === InvoiceStatus::PAID) {
            Notification::make()
                ->success()
                ->title('Payment Verified')
                ->body('Your payment has been successfully verified!')
                ->send();

            $this->redirect($this->invoice->redirect_url);

            return;
        }

        if (($this->invoice->metadata['verification_status'] ?? null) === 'failed') {
            $this->isVerifying = false;
            $this->verificationFailed = true;
            $this->verificationError = $this->invoice->metadata['verification_error'] ?? 'Payment verification failed.';

            Notification::make()
                ->danger()
                ->title('Verification Failed')
                ->body($this->verificationError)
                ->send();

            return;
        }

        Notification::make()
            ->info()
            ->title('Verification in Progress')
            ->body('Your payment receipt was uploaded. Our AI is verifying it against bank confirmation SMS.')
            ->send();
    }

    public function checkVerificationStatus(): void
    {
        $this->invoice->refresh();

        if ($this->invoice->status === InvoiceStatus::PAID) {
            $this->isVerifying = false;
            Notification::make()
                ->success()
                ->title('Payment Verified!')
                ->body('Your payment has been successfully confirmed.')
                ->send();

            $this->redirect($this->invoice->redirect_url);

            return;
        }

        $metadata = $this->invoice->metadata ?? [];
        $status = $metadata['verification_status'] ?? null;

        if ($status === 'failed') {
            $this->isVerifying = false;
            $this->verificationFailed = true;
            $this->verificationError = $metadata['verification_error'] ?? 'Payment verification failed. Please contact support.';

            Notification::make()
                ->danger()
                ->title('Verification Incomplete')
                ->body($this->verificationError)
                ->send();

            return;
        }

        // Frontend 10-minute timeout check
        $startedAt = isset($metadata['verification_started_at'])
            ? Carbon::parse($metadata['verification_started_at'])
            : null;
        $timeoutMinutes = (int) config('payment.timeout_minutes', 10);

        if ($startedAt && $startedAt->diffInMinutes(now()) >= $timeoutMinutes) {
            $this->isVerifying = false;
            $this->verificationFailed = true;
            $this->verificationError = 'Verification timed out after 10 minutes. Please contact support.';

            $metadata['verification_status'] = 'failed';
            $metadata['verification_error'] = $this->verificationError;
            $this->invoice->update(['metadata' => $metadata]);

            Notification::make()
                ->danger()
                ->title('Verification Timed Out')
                ->body($this->verificationError)
                ->send();
        }
    }

    public function resetVerification(): void
    {
        $this->isVerifying = false;
        $this->verificationFailed = false;
        $this->verificationError = null;
        $this->screenshot = null;
        $this->transactionId = null;

        $metadata = $this->invoice->metadata ?? [];
        unset($metadata['verification_status'], $metadata['verification_error'], $metadata['verification_started_at']);

        $this->invoice->update([
            'metadata' => $metadata,
        ]);
    }
}
