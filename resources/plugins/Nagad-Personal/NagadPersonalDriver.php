<?php

namespace App\Plugins\NagadPersonal;

use App\Payment\PaymentDriver;
use App\Plugins\Facades\PluginManager;
use App\Plugins\Plugin;

class NagadPersonalDriver extends PaymentDriver
{
    public function getType(): string
    {
        return 'mobile';
    }

    // Add payment processing methods here
}
