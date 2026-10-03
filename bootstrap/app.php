<?php

use App\Http\Middleware\ErpSecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            $erpDomain = config('app.erp_domain', 'app.bimabergawa.com');
            $publicDomain = config('app.public_domain', 'bimabergawa.com');

            // 1. Subdomain ERP: app.bimabergawa.test / app.bimabergawa.com
            Route::middleware(['web', ErpSecurityHeadersMiddleware::class])
                ->domain($erpDomain)
                ->group(base_path('routes/erp.php'));

            // 2. Domain Publik: bimabergawa.test / bimabergawa.com
            Route::middleware('web')
                ->domain($publicDomain)
                ->group(base_path('routes/web.php'));

            // 3. Fallback routing for non-domain CLI / testing requests
            if (app()->runningUnitTests()) {
                Route::middleware(['web', ErpSecurityHeadersMiddleware::class])
                    ->group(base_path('routes/erp.php'));
            }

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
