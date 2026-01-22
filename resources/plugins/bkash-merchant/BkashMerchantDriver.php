<?php

namespace App\Plugins\BkashMerchant;

use App\Payment\PaymentDriver;

class BkashMerchantDriver extends PaymentDriver
{
    public function getType(): string
    {
        return 'mobile';
    }

    // Add payment processing methods here
}
