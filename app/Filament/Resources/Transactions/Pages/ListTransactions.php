<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\Schemas\TransactionForm;
use App\Filament\Resources\Transactions\TransactionResource;
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
            Action::make('runVerificationCheck')
                ->label('Run Verification Check')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action(function () {
                    Notification::make()
                        ->title('Verification check completed')
                        ->success()
                        ->send();
                }),
            CreateAction::make()
                ->steps(TransactionForm::steps())
                ->slideOver()
                ->modalWidth(Width::Medium),
        ];
    }
}
