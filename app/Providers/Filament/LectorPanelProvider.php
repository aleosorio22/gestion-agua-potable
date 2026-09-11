<?php

namespace App\Providers\Filament;

use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * La herramienta del lector en la calle.
 *
 * Va en panel aparte y no en /admin con permisos recortados porque el problema
 * no es de seguridad sino de forma: la densidad del panel de oficina —cuatro
 * grupos, trece pantallas, un tablero de cobranza— es una virtud frente a una
 * computadora y un estorbo con el celular en una mano y el medidor en la otra.
 *
 * Sin barra lateral y sin tablero: al entrar, el lector cae directo en su ruta.
 */
class LectorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('lector')
            ->path('lector')
            ->login()
            ->brandName('Ruta de lectura')
            ->colors([
                'primary' => Color::Emerald,
            ])
            // Claro y fijo: en la calle se lee con sol de frente, y el modo
            // oscuro a pleno día es ilegible.
            ->defaultThemeMode(ThemeMode::Light)
            ->darkMode(false)
            // Sin barra lateral: hay una sola pantalla, no hay dónde navegar.
            ->topNavigation()
            ->maxContentWidth('3xl')
            ->discoverPages(in: app_path('Filament/Lector/Pages'), for: 'App\Filament\Lector\Pages')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('@include("filament.lector.estilos")'),
            )
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
            ]);
    }
}
