<?php

namespace App\Enums\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum DeviceStatus: string implements HasColor, HasIcon, HasLabel
{
    case CONNECTED = 'connected';
    case DISCONNECTED = 'disconnected';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::CONNECTED => 'Connected',
            self::DISCONNECTED => 'Disconnected',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::CONNECTED => 'heroicon-o-check-circle',
            self::DISCONNECTED => 'heroicon-o-x-circle',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::CONNECTED => 'success',
            self::DISCONNECTED => 'danger',
        };
    }
}
