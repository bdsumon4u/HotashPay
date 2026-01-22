<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Contracts\Support\Htmlable;

class LoginPage extends Login
{
    public function getSubheading(): string|Htmlable|null
    {
        return User::query()->exists() ? null : parent::getSubheading();
    }
}
