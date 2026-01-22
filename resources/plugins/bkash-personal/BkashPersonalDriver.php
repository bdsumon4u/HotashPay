<?php

namespace App\Plugins\BkashPersonal;

use App\Payment\PaymentDriver;

class BkashPersonalDriver extends PaymentDriver
{
    public function getType(): string
    {
        return 'mobile';
    }

    // Add payment processing methods here
}
