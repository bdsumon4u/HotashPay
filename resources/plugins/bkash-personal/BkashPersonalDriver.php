<?php

namespace App\Plugins\BkashPersonal;

use App\Payment\PaymentDriver;

class BkashPersonalDriver extends PaymentDriver
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
            '/You have received Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+)\. Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/',
            '/You have received Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+)\. Ref .*?Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/',
            '/You have received Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+)\.Ref .*?TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/',
        ];
    }

    // Add payment processing methods here
}
