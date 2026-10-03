<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\PasswordlessLogin;
use App\Http\Middleware\ErpSecurityHeadersMiddleware;
use App\Http\Middleware\FilamentOtpAuthenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AgentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('mitra')
            ->path('mitra')
            ->login(PasswordlessLogin::class)
            ->colors([
                'primary' => Color::Zinc,
            ])
            ->font('Inter')
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Agent/Resources'), for: 'App\\Filament\\Agent\\Resources')
            ->discoverPages(in: app_path('Filament/Agent/Pages'), for: 'App\\Filament\\Agent\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Agent/Widgets'), for: 'App\\Filament\\Agent\\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                ErpSecurityHeadersMiddleware::class,
            ])
            ->authMiddleware([
                FilamentOtpAuthenticate::class,
            ]);

        if (! app()->environment('local') && ($erpDomain = config('app.erp_domain', 'app.bimabergawa.com'))) {
            $panel->domain($erpDomain);
        }

        return $panel;
    }
}
