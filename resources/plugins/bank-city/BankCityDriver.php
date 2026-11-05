<?php

namespace App\Plugins\BankCity;

use App\Payment\PaymentDriver;

class BankCityDriver extends PaymentDriver
{
    public function getType(): string
    {
        return 'ibanking';
    }

    public function getAliases(): array
    {
        return ['City Bank', 'Bank City', 'bank-city', 'CityBank', 'Citytouch', 'citytouch', 'city_bank', 'CITY', 'সিটি ব্যাংক', 'সিটি', 'City'];
    }

    public function getReceiptPromptNotes(): ?string
    {
        return 'City Bank / Citytouch "Reference No" is the primary transaction identifier: copy the exact Reference No to both trx_id and reference.';
    }

    public function getMessageFormats(): array
    {
        return [
            '/A\/C\s+(?<receiver_account>\d+\*+\d+)\s+is credited with BDT\s+(?<amount>[\d,]+(?:\.\d{1,2})?)\s+on\s+(?<trxid>\d{2}-[A-Z]{3}-\d{2}\s+\d{2}:\d{2}:\d{2}\s+[AP]M).+?Balance\s+is\s+BDT\s+(?<balance>[\d,]+(?:\.\d{1,2})?)/i',
            '/AC\s+(?<receiver_account>\d+\*+\d+)\s+credited\s+BDT\s+(?<amount>[\d,]+(?:\.\d{1,2})?)\s+Ref:\s*(?<trxid>[A-Z0-9]+)/i',
            '/(?:TrxID|Ref|Reference)[:\s]*(?<trxid>[A-Z0-9]+).+?credited with BDT\s+(?<amount>[\d,]+(?:\.\d{1,2})?)/i',
            '/(?:CITYTOUCH\s+TXN|Deposit)\s+(?:Tk\.?|BDT)\s*(?<amount>[\d,]+(?:\.\d{1,2})?).+?(?:to|into)\s+(?:A\/C\s*)?(?<receiver_account>[\d*]+)/i',
            '/(?<date>\d{2}-[A-Za-z]{3}-\d{4})\s+(?:Tk\.?|BDT)\s*(?<amount>[\d,]+(?:\.\d{1,2})?)\s+Deposit\s+Tk\.?\s*(?<balance>[\d,]+(?:\.\d{1,2})?).+?(?:to|into)\s+(?:A\/C\s*)?(?<receiver_account>[\d*]+)/i',
        ];
    }
}
