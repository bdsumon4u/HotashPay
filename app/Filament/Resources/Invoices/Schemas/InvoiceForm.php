<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('client_name')
                    ->label('Name')
                    ->required()
                    ->default('Mr. X'),
                TextInput::make('client_email')
                    ->label('Email')
                    ->email(),
                TextInput::make('client_phone')
                    ->label('Phone')
                    ->tel(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->default('BDT'),
                Select::make('status')
                    ->options(InvoiceStatus::class)
                    ->enum(InvoiceStatus::class)
                    ->required()
                    ->native(false),
                Textarea::make('redirect_url')
                    ->columnSpanFull(),
                Textarea::make('cancel_url')
                    ->columnSpanFull(),
                Textarea::make('webhook_url')
                    ->columnSpanFull(),
            ]);
    }
}
