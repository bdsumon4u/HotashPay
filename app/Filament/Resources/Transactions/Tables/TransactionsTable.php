<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Payment\PaymentManager;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                TextColumn::make('entry_type')
                    ->searchable(),
                TextColumn::make('provider')
                    ->searchable(),
                TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('mobile')
                    ->searchable(),
                TextColumn::make('trxid')
                    ->searchable(),
                TextColumn::make('balance')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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
                    ->modal()
                    ->modalWidth(Width::Small),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
