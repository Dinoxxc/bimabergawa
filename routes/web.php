<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentVerificationController;
use App\Http\Controllers\PrivateFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/private-documents/download', [PrivateFileController::class, 'download'])
    ->name('private.file.download')
    ->middleware('signed');

Route::get('/verify-doc/{type}/{hash}', [DocumentVerificationController::class, 'verify'])
    ->name('public.document.verify');
