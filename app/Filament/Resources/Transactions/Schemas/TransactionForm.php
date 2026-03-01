<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('provider')
                    ->label('Sender')
                    ->required(),
                TextInput::make('status')
                    ->disabled()
                    ->dehydrated(),
                Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('mobile')
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('trxid')
                    ->label('Transaction ID')
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('balance')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),
            ])
            ->columns(2);
    }
}
