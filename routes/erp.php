<?php

declare(strict_types=1);

use App\Http\Controllers\AgentRegistrationController;
use App\Http\Controllers\AuthOtpController;
use App\Http\Controllers\CatalogBulkImportController;
use App\Http\Controllers\DocumentVerificationController;
use App\Http\Controllers\PanelRedirectionController;
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

// 1. Root Intelligent Redirection (Option B Dispatch)
Route::get('/', [PanelRedirectionController::class, 'index'])->name('erp.root');

// 2. Passwordless Email OTP Authentication Flow
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthOtpController::class, 'showLoginForm'])->name('login');
    Route::get('/auth/login', [AuthOtpController::class, 'showLoginForm'])->name('auth.otp.login');
    Route::post('/login/send-otp', [AuthOtpController::class, 'sendOtp'])->name('auth.otp.send');
    Route::post('/login/verify-otp', [AuthOtpController::class, 'verifyOtp'])->name('auth.otp.verify');

    // 3. Freelance Partner Onboarding & Registration (TC-AGN-REG-001)
    Route::get('/mitra/register', [AgentRegistrationController::class, 'showRegistrationForm'])->name('agent.register');
    Route::post('/mitra/register', [AgentRegistrationController::class, 'register'])->name('agent.register.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthOtpController::class, 'logout'])->name('auth.otp.logout');

    // Catalog Bulk Import & Template Download (TC-CAT-IMP-001 s.d. TC-CAT-IMP-016)
    Route::prefix('admin/catalog')->group(function () {
        Route::get('/template', [CatalogBulkImportController::class, 'downloadTemplate'])->name('catalog.template.download');
        Route::post('/bulk-import', [CatalogBulkImportController::class, 'import'])->name('catalog.bulk.import');
    });
});

// 4. Digital QR Code Document Verification Portal (Public, Zero-Auth)
Route::get('/verify-doc/{type}/{hash}', [DocumentVerificationController::class, 'verify'])
    ->name('document.verify');
