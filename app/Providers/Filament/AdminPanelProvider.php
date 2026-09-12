<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Widgets\EstadoDeCuentaClientes;
use App\Filament\Admin\Widgets\ResumenCobranza;
use App\Filament\Admin\Widgets\TrabajoPendiente;
use App\Http\Middleware\RedirigirSiNoEstaInstalado;
use App\Support\IdentidadDeLaEntidad;
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
            // Closures y no valores: un PanelProvider se construye en cada
            // arranque, incluso corriendo `migrate` sobre una base vacía.
            // Consultar la tabla ahí rompería la instalación antes de existir.
            ->brandName(fn (): string => app(IdentidadDeLaEntidad::class)->nombre())
            ->brandLogo(fn (): ?string => app(IdentidadDeLaEntidad::class)->logo())
            ->brandLogoHeight('2.25rem')
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
                GrupoNavegacion::Seguridad->getLabel(),
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
            ->plugins([
                // Los roles son administración de la oficina, no una sección
                // aparte que se llame como el paquete que la provee: la
                // traducción del propio Shield deja «Filament Shield» en
                // español, que a la junta no le dice nada.
                // Grupo propio y no «Administración»: Filament no fusiona el
                // grupo de un Resource —que llega como enum— con el de un
                // plugin —que llega como texto—, y el menú terminaba con
                // «Administración» dos veces. La traducción del propio Shield
                // deja «Filament Shield» en español, que a la junta no le dice
                // nada.
                FilamentShieldPlugin::make()
                    ->navigationGroup(GrupoNavegacion::Seguridad->getLabel())
                    ->navigationLabel('Roles y permisos'),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
