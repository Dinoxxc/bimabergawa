<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Agent\Widgets\AgentCommissionOverviewWidget;
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
use Filament\View\PanelsRenderHook;
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
            ->brandName('PT. Panca Bima Bergawa - Portal Mitra')
            ->brandLogo(fn () => view('filament.components.brand-logo'))
            ->brandLogoHeight('2.25rem')
            ->colors([
                'primary' => [
                    50 => '#f0f9ff',
                    100 => '#e0f2fe',
                    200 => '#bae6fd',
                    300 => '#7dd3fc',
                    400 => '#38bdf8',
                    500 => '#46a2da',
                    600 => '#2b8ac4',
                    700 => '#1d6f9f',
                    800 => '#1b5b82',
                    900 => '#1b4d6d',
                    950 => '#0b2333',
                ],
                'gray' => Color::Zinc,
            ])
            ->font('Inter')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.hooks.head-styles'),
            )
            ->sidebarWidth('16rem')
            ->discoverResources(in: app_path('Filament/Agent/Resources'), for: 'App\\Filament\\Agent\\Resources')
            ->discoverPages(in: app_path('Filament/Agent/Pages'), for: 'App\\Filament\\Agent\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Agent/Widgets'), for: 'App\\Filament\\Agent\\Widgets')
            ->widgets([
                AgentCommissionOverviewWidget::class,
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

        if ($erpDomain = config('app.erp_domain', 'app.bimabergawa.com')) {
            $panel->domain($erpDomain);
        }

        return $panel;
    }
}
