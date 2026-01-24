<?php

namespace App\Plugins\BkashMerchant;

use App\Payment\PaymentDriver;

class BkashMerchantDriver extends PaymentDriver
{
    public function getType(): string
    {
        return 'mobile';
    }

    public function getAliases(): array
    {
        return ['bKash'];
    }

    public function getMessageFormats(): array
    {
        return [
            '/You have received payment Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+)\. Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/',
        ];
    }

    // Add payment processing methods here
}
