<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class PasswordlessLogin extends BaseLogin
{
    /**
     * Mengarahkan pengguna ke formulir shared Passwordless Email OTP Login.
     */
    public function mount(): void
    {
        parent::mount();

        redirect()->route('login');
    }
}
