<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Subdomain ERP Routes (app.bimabergawa.com)
|--------------------------------------------------------------------------
|
| Rute operasional internal Commercial ERP PT. Panca Bima Bergawa.
| Terisolasi di bawah subdomain ERP dan diamankan via Passwordless Email OTP.
|
*/

Route::get('/', function () {
    return redirect()->to('/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('welcome'); // Placeholder until AuthOtpController is implemented in feature/erp-01
    })->name('filament.admin.auth.login');
});
