<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Payment\SmsParser;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
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
                ->action(function (Action $action, array $data) {
                    if (isset($data['provider']) && isset($data['message'])) {
                        $parsed = SmsParser::parse(
                            $data['provider'],
                            $data['message'],
                            now()->toDateTimeString(),
                        );

                        if (! $parsed) {
                            Notification::make()
                                ->title('Failed to parse SMS')
                                ->body('Please check the SMS format and try again.')
                                ->danger()
                                ->send();

                            return $action->halt();
                        }

                        $data = array_merge($data, $parsed);
                    }

                    return static::getResource()::getModel()::create($data);
                }),
        ];
    }
}
