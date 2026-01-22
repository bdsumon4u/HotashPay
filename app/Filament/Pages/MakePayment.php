<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Payment\PaymentManager;
use Filament\Pages\SimplePage;

class MakePayment extends SimplePage
{
    protected bool $hasTopbar = false;

    protected string $view = 'filament.pages.make-payment';

    public Invoice $invoice;

    public array $gateways = [];

    public function mount(Invoice $invoice, PaymentManager $paymentManager): void
    {
        $this->invoice = $invoice;

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
}
