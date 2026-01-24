<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Payment\SmsParser;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modal()
                ->modalWidth(Width::Small)
                ->action(function (array $data) {
                    if (isset($data['from']) && isset($data['message'])) {
                        $parsed = SmsParser::parse(
                            $data['from'],
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

                    unset($data['from']);
                    $data['entry_type'] = 'manual';
                    $data['sim'] = 'NULL';

                    return static::getResource()::getModel()::create($data);
                }),
        ];
    }
}
