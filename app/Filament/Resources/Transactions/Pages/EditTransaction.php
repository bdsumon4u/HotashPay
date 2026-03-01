<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Payment\SmsParser;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['provider']) && isset($data['message'])) {
            $data += SmsParser::parse(
                $data['provider'],
                $data['message'],
                now()->toDateTimeString(),
            ) ?? [];
        }

        return $data;
    }
}
