<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login()
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('2.2rem')
            ->favicon(asset('images/logo.png'))
            ->colors([
                // Blue primary — clean, professional, great white-text contrast in both light and dark mode
                'primary' => Color::Blue,
                // Slate gray — cool blue-gray neutrals that complement the blue primary
                'gray'    => Color::Slate,
            ])
            ->navigationGroups([
                // ── Records ──────────────────────────────────────────────────
                // 10. Visit History  → VisitResource
                // 20. Visitors       → VisitorResource
                // 30. Devices        → StationDeviceLogResource
                // To insert between 20 and 30, use sort = 25 in the new Resource
                'Records',
                // ── Management ───────────────────────────────────────────────
                // 10. Stations       → StationResource
                'Management',
                // ── System ───────────────────────────────────────────────────
                // 10. Users          → UserResource
                // 20. Countries      → CountryResource
                'System',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook('panels::head.end', fn() => '<style>[x-cloak]{display:none!important}</style>');
    }
}
