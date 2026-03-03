<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_id')
                    ->label('Invoice ID')
                    ->required()
                    ->default(fn (): string => Str::random(10)),
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
                Radio::make('status')
                    ->options(InvoiceStatus::class)
                    ->default(InvoiceStatus::PENDING)
                    ->required()
                    ->inline()
                    ->columnSpanFull(),
                Textarea::make('redirect_url')
                    ->columnSpanFull(),
                Textarea::make('cancel_url')
                    ->columnSpanFull(),
                Textarea::make('webhook_url')
                    ->columnSpanFull(),
            ]);
    }
}
