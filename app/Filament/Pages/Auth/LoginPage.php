<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login;
use Illuminate\Contracts\Support\Htmlable;

class LoginPage extends Login
{
    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }
}
