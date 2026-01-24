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

    public function getAliases(): array
    {
        return ['NAGAD', 'Nagad', '01708403334'];
    }

    public function getMessageFormats(): array
    {
        return [
            '/Money Received\.\s*Amount: Tk (?<amount>[\d,]+\.\d{2})\s*Sender: (?<mobile>\d+)\s*Ref: (.+)\s*TxnID: (?<trxid>\w+)\s*Balance: Tk (?<balance>[\d,]+\.\d{2})\s*(?<date>\d{2}\/\d{2}\/\d{4}) (?<time>\d{2}:\d{2})/',
        ];
    }

    // Add payment processing methods here
}
