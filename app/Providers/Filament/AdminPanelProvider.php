<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Widgets\EstadoDeCuentaClientes;
use App\Filament\Admin\Widgets\ResumenCobranza;
use App\Filament\Admin\Widgets\TrabajoPendiente;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
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
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            // Sin esto el orden del menú lo decide el descubrimiento de
            // recursos, que es alfabético por directorio.
            ->navigationGroups([
                GrupoNavegacion::Padron->getLabel(),
                GrupoNavegacion::Operacion->getLabel(),
                GrupoNavegacion::Catalogos->getLabel(),
                GrupoNavegacion::Administracion->getLabel(),
            ])
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            // El tablero muestra el negocio, no la tarjeta de la cuenta ni el
            // logo de Filament que vienen de fábrica.
            ->widgets([
                ResumenCobranza::class,
                TrabajoPendiente::class,
                EstadoDeCuentaClientes::class,
            ])
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
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
