<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;

/**
 * Custom authentication login page providing a 30-day "Keep me logged in" toggle.
 */
class Login extends BaseLogin
{
    /**
     * Get the "remember me" form component configured as a 30-day toggle.
     */
    protected function getRememberFormComponent(): Component
    {
        return Toggle::make('remember')
            ->label('Keep me logged in for up to 30 days')
            ->default(false);
    }
}
