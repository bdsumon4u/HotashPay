<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Payment\SmsParser;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::steps());
    }

    public static function receivedDataSchema(): array
    {
        return [
            TextInput::make('provider')
                ->label('Sender')
                ->required(),
            Textarea::make('message')
                ->required()
                ->rows(5),
        ];
    }

    public static function parsedDataSchema(): array
    {
        return [
            Textarea::make('message')
                ->rows(5)
                ->readOnly()
                ->columnSpanFull()
                ->hiddenOn(Operation::Create),
            TextInput::make('mobile')
                ->required(),
            TextInput::make('trxid')
                ->label('Transaction ID'),
            TextInput::make('amount')
                ->numeric()
                ->required(),
            TextInput::make('balance')
                ->numeric(),
            TextInput::make('provider')
                ->readOnly(),
            TextInput::make('status')
                ->dehydrateStateUsing(fn () => 'approved')
                ->readOnly(),
            Hidden::make('received_at')
                ->default(fn () => now()->toDateTimeString()),
            TextEntry::make('approval_notice')
                ->label('Approval Notice')
                ->color('warning')
                ->icon(Heroicon::ExclamationTriangle)
                ->visible(fn (Get $get) => $get('status') === 'review')
                ->default('The transaction will automatically be approved after saving.')
                ->columnSpanFull(),
        ];
    }

    public static function steps(): array
    {
        return [
            Step::make('Received Data')
                ->schema(self::receivedDataSchema())
                ->afterValidation(function (Get $get, Set $set) {
                    if (! self::parseMessage($get, $set)) {
                        Notification::make()
                            ->title('Parsing Error')
                            ->danger()
                            ->send();

                        throw new Halt;
                    }
                }),
            Step::make('Parsed Data')
                ->schema(self::parsedDataSchema())
                ->columns(2),
        ];
    }

    public static function parseMessage(Get $get, Set $set): bool
    {
        $provider = $get('provider');
        $message = $get('message');

        if (empty($provider) || empty($message)) {
            return false;
        }

        $parsed = SmsParser::parse(
            $provider,
            $message,
            now()->toDateTimeString(),
        );

        if (! $parsed) {
            return false;
        }

        foreach ($parsed as $key => $value) {
            $set($key, $value);
        }

        return true;
    }
}
