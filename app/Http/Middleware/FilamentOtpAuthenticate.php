<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as BaseAuthenticate;

class FilamentOtpAuthenticate extends BaseAuthenticate
{
    /**
     * Mengarahkan pengguna yang belum terotentikasi ke rute shared Passwordless OTP Login.
     */
    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
