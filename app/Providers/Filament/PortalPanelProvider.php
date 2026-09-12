<?php

namespace App\Providers\Filament;

use App\Http\Middleware\RedirigirSiNoEstaInstalado;
use App\Support\IdentidadDeLaEntidad;
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

class PortalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('portal')
            ->path('portal')
            ->login()
            // El vecino entra desde un enlace: el nombre de su oficina es lo
            // que le confirma que la página es la que dice ser.
            ->brandName(fn (): string => app(IdentidadDeLaEntidad::class)->nombre())
            ->brandLogo(fn (): ?string => app(IdentidadDeLaEntidad::class)->logo())
            ->brandLogoHeight('2.25rem')
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->discoverResources(in: app_path('Filament/Portal/Resources'), for: 'App\Filament\Portal\Resources')
            ->discoverPages(in: app_path('Filament/Portal/Pages'), for: 'App\Filament\Portal\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->middleware([
                // Los paneles no pasan por el grupo `web`, así que el guardián
                // del instalador se declara acá también: sin esto, un servidor
                // recién montado manda a /admin/login en vez de al asistente.
                RedirigirSiNoEstaInstalado::class,
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
            ]);
    }
}
