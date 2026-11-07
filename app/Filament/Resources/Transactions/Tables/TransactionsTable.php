<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Transactions\Schemas\TransactionForm;
use App\Payment\PaymentManager;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('provider')
                    ->label('Provider')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => app(PaymentManager::class)->findProvider($state)?->getName() ?? ucfirst((string) $state))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('trxid')
                    ->label('TrxID / Ref')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => 'BDT '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('receiver_account')
                    ->label('Receiver A/C')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('sender_account')
                    ->label('Sender A/C')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('received_at')
                    ->label('Tx Time')
                    ->dateTime('d M Y h:i A')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('message')
                    ->label('SMS Content')
                    ->limit(55)
                    ->tooltip(fn ($record) => $record->message)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'success',
                        default => 'warning',
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('invoice.invoice_id')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->invoice_id ? InvoiceResource::getUrl('view', ['record' => $record->invoice_id]) : null)
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('mobile')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('balance')
                    ->formatStateUsing(fn ($state) => 'BDT '.number_format((float) $state, 2))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('entry_type')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('entry_type')
                    ->options([
                        'manual' => 'Manual',
                        'automatic' => 'Automatic',
                    ])
                    ->native(false),
                SelectFilter::make('provider')
                    ->options(collect(app(PaymentManager::class)->getDrivers())->mapWithKeys(fn ($driver) => [$driver->getId() => $driver->getName()]))
                    ->native(false),
                SelectFilter::make('status')
                    ->options([
                        'review' => 'Review',
                        'approved' => 'Approved',
                    ])
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->schema(fn (Schema $schema) => $schema->components(
                        TransactionForm::parsedDataSchema()
                    )->columns(2))
                    ->slideOver()
                    ->modalWidth(Width::Medium),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
