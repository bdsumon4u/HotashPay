<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Payment\SmsParser;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['sim']) && isset($data['message'])) {
            $parsed = SmsParser::parse(
                $data['sim'],
                $data['message'],
                now()->toDateTimeString()
            );

            if ($parsed) {
                $data['provider'] = $parsed['provider'];
                $data['amount'] = $parsed['amount'];
                $data['mobile'] = $parsed['mobile'];
                $data['trxid'] = $parsed['trxid'];
                $data['balance'] = $parsed['balance'];
                $data['status'] = $parsed['status'];
            }
        }

        return $data;
    }
}
