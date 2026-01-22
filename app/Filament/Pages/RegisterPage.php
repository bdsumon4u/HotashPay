<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register;
use Illuminate\Validation\ValidationException;

class RegisterPage extends Register
{
    public function register(): ?RegistrationResponse
    {
        if (User::query()->exists()) {
            throw ValidationException::withMessages([
                'data.email' => 'Registration is disabled.',
            ]);
        }

        return parent::register();
    }
}
