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
use Illuminate\Support\HtmlString;
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
            ->brandLogo(new HtmlString(
                '<img src="' . asset('images/logo.png') . '" class="efl-logo-img" alt="EFL">' .
                '<span class="efl-logo-text">Access Portal</span>'
            ))
            ->brandLogoHeight('2rem')
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
            ->renderHook('panels::head.end', fn() => new HtmlString('<style>
[x-cloak]{display:none!important}

/* ── Sidebar / topbar logo ──────────────────────────────────── */
.fi-sidebar-header { justify-content:center; }
.fi-logo { display:flex; align-items:center; justify-content:center; }
.efl-logo-img  { height:1.7rem; display:block; }
.efl-logo-text { margin-left:.55rem; font-size:.95rem; font-weight:600;
                 white-space:nowrap; color:inherit; }

/* ── Login page logo ────────────────────────────────────────── */
/* Override Filament inline height on the container             */
.fi-simple-header .fi-logo {
    height:auto !important;
    display:flex;
    flex-direction:column;
    align-items:center;
    margin-bottom:1.75rem;
}
.fi-simple-header .efl-logo-img  { height:5rem; width:auto; display:block; }
.fi-simple-header .efl-logo-text { display:none; }
</style>'))
            ->renderHook('panels::body.end', fn() => view('partials.lightbox-bootstrap'))
            ->renderHook('panels::body.end', fn() => new HtmlString(<<<'HTML'
<script>
(function () {
    if (window.__eflTzBound) return;
    window.__eflTzBound = true;

    var fmt = new Intl.DateTimeFormat(undefined, {
        year:   'numeric',
        month:  '2-digit',
        day:    '2-digit',
        hour:   '2-digit',
        minute: '2-digit',
        hour12: false,
    });

    function apply() {
        document.querySelectorAll('.efl-tz').forEach(function (el) {
            if (el.dataset.eflTzApplied === '1') return;
            var utc = el.dataset.utc;
            if (!utc) return;
            try {
                var d = new Date(utc);
                if (isNaN(d.getTime())) return;
                el.title = 'Tu hora local: ' + fmt.format(d);
                el.dataset.eflTzApplied = '1';
            } catch (e) {}
        });
    }

    document.addEventListener('DOMContentLoaded', apply);
    document.addEventListener('livewire:navigated', apply);
    document.addEventListener('livewire:initialized', apply);

    // Filament suele abrir modales / popovers de forma asíncrona; re-aplicar
    // ante cualquier cambio del DOM (barato porque cacheamos con data-set).
    var debounce;
    new MutationObserver(function () {
        clearTimeout(debounce);
        debounce = setTimeout(apply, 60);
    }).observe(document.body, { childList: true, subtree: true });
})();
</script>
HTML));
    }
}
