<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use Filament\Pages\SimplePage;

class MakePayment extends SimplePage
{
    protected bool $hasTopbar = false;

    protected string $view = 'filament.pages.make-payment';

    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }
}
