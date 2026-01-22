<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction as ActionsDeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ApiTokens extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.api-tokens';

    protected static ?string $navigationLabel = 'API Tokens';

    protected static ?string $title = 'API Tokens';

    protected static ?int $navigationSort = 100;

    public ?string $plainTextToken = null;

    private function createAction(): Action
    {
        return Action::make('createToken')
            ->label('Create New Token')
            ->icon('heroicon-o-plus')
            ->schema([
                TextInput::make('name')
                    ->label('Token Name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('A descriptive name to help you identify this token.'),
                CheckboxList::make('abilities')
                    ->label('Permissions')
                    ->options([
                        'read' => 'Read',
                        'create' => 'Create',
                        'update' => 'Update',
                        'delete' => 'Delete',
                    ])
                    ->columns(2)
                    ->helperText('Select the permissions this token should have. Leave empty for full access.')
                    ->default([]),
                DateTimePicker::make('expires_at')
                    ->label('Expiration Date')
                    ->helperText('Leave empty for no expiration.')
                    ->nullable()
                    ->native(false)
                    ->seconds(false)
                    ->minDate(now()->addMinute()),
            ])
            ->modalWidth('2xl')
            ->action(function (array $data): void {
                $abilities = empty($data['abilities']) ? ['*'] : $data['abilities'];

                $expiresAt = null;
                if ($data['expires_at']) {
                    $expiresAt = $data['expires_at'] instanceof \DateTimeInterface
                        ? $data['expires_at']
                        : \Illuminate\Support\Carbon::parse($data['expires_at']);
                }

                $token = $this->getUser()->createToken(
                    $data['name'],
                    $abilities,
                    $expiresAt
                );

                $this->plainTextToken = $token->plainTextToken;

                Notification::make()
                    ->success()
                    ->title('Token Created')
                    ->body('Your API token has been created. Make sure to copy it now as you won\'t be able to see it again.')
                    ->persistent()
                    ->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->createAction(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->getUser()
                    ->tokens()
                    ->getQuery()
                    ->latest()
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->weight('bold'),
                TextColumn::make('abilities')
                    ->label('Permissions')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime()
                    ->placeholder('Never')
                    ->since()
                    ->description(fn ($record) => $record->last_used_at?->format('M d, Y g:i A')),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->placeholder('Never')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : ($state && $state->diffInDays() < 7 ? 'warning' : 'success'))
                    ->description(fn ($record) => $record->expires_at ? ($record->expires_at->isPast() ? 'Expired' : 'Expires '.$record->expires_at->diffForHumans()) : null),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y g:i A')),
            ])
            ->recordActions([
                ActionsDeleteAction::make()
                    ->label('Revoke')
                    ->modalHeading('Revoke API Token')
                    ->modalDescription('Are you sure you want to revoke this API token? This action cannot be undone.')
                    ->successNotificationTitle('Token Revoked')
                    ->icon('heroicon-o-trash'),
            ])
            ->emptyStateHeading('No API Tokens')
            ->emptyStateDescription('You haven\'t created any API tokens yet. Create one to get started.')
            ->emptyStateIcon('heroicon-o-key')
            ->emptyStateActions([
                $this->createAction(),
            ])
            ->striped();
    }

    public function closeTokenModal(): void
    {
        $this->plainTextToken = null;
    }

    private function getUser(): User
    {
        return Auth::user();
    }
}
