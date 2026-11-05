<?php

namespace App\Plugins\BankEbl;

use App\Payment\PaymentDriver;

class BankEblDriver extends PaymentDriver
{
    public function getType(): string
    {
        return 'ibanking';
    }

    public function getAliases(): array
    {
        return ['EBL', 'Bank EBL', 'Eastern Bank', 'Eastern Bank Ltd', 'Eastern Bank PLC', 'ebl', 'bank_ebl', 'bank-ebl', 'ইস্টার্ন ব্যাংক', 'ইস্টার্ন'];
    }

    public function getReceiptPromptNotes(): ?string
    {
        return 'EBL "REF:" is a reference number: set reference, and set trx_id if a separate transaction ID is explicitly shown.';
    }

    public function getMessageFormats(): array
    {
        return [
            '/AC\s+(?<receiver_account>\d+\*+\d+)\s+is credited with BDT\s+(?<amount>[\d,]+(?:\.\d{1,2})?)\s+as\s+.+?\s+on\s+(?<trxid>\d{2}-[A-Z]{3}-\d{2}\s+\d{2}:\d{2}:\d{2}\s+[AP]M)\s+Balance is BDT\s+(?<balance>[\d,]+(?:\.\d{1,2})?)\s+Thanks\.\s+EBL Helpline\s+\d+/i',
            '/AC\s+(?<receiver_account>\d+\*+\d+)\s+is credited with BDT\s+(?<amount>[\d,]+(?:\.\d{1,2})?)/i',
        ];
    }
}
