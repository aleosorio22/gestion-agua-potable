<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Filament\Panel;
use Spatie\Permission\Models\Role;

/**
 * Estas pruebas cubren el 403 silencioso de Filament: si User no implementa
 * FilamentUser, el middleware Authenticate aborta con 403 a todo el mundo en
 * cuanto APP_ENV deja de ser "local".
 */
function panelAdmin(): Panel
{
    return Filament::getPanel('admin');
}

function panelLector(): Panel
{
    return Filament::getPanel('lector');
}

function usuarioConRol(string $rol): User
{
    Role::findOrCreate($rol, 'web');

    return User::factory()->create()->assignRole($rol);
}

it('deja entrar al panel de oficina a los roles de oficina', function (string $rol) {
    expect(usuarioConRol($rol)->canAccessPanel(panelAdmin()))->toBeTrue();
})->with(['Administrador', 'Secretaria']);

it('mantiene la lista de roles del panel alineada con la configuracion', function () {
    expect(config('admin.panel_roles'))
        ->toEqualCanonicalizing(['Administrador', 'Secretaria'])
        ->and(config('lector.panel_roles'))->toEqualCanonicalizing(['Lector', 'Administrador'])
        ->and(config('admin.roles'))->toContain('Cliente');
});

it('deja al lector en su ruta y fuera del panel de oficina', function () {
    $lector = usuarioConRol('Lector');

    // Su herramienta vive en /lector; dejarle además /admin solo le daría
    // lugares donde perderse, y sigue siendo administrable desde ahí.
    expect($lector->canAccessPanel(panelLector()))->toBeTrue()
        ->and($lector->canAccessPanel(panelAdmin()))->toBeFalse();
});

it('deja al administrador entrar tambien a la ruta de lectura', function () {
    expect(usuarioConRol('Administrador')->canAccessPanel(panelLector()))->toBeTrue();
});

it('no deja a la secretaria en la ruta de lectura', function () {
    expect(usuarioConRol('Secretaria')->canAccessPanel(panelLector()))->toBeFalse();
});

it('no deja al cliente en la ruta de lectura', function () {
    expect(usuarioConRol('Cliente')->canAccessPanel(panelLector()))->toBeFalse();
});

it('deja entrar al super admin a los dos paneles internos', function () {
    $rol = config('filament-shield.super_admin.name');
    $usuario = usuarioConRol($rol);

    expect($usuario->canAccessPanel(panelAdmin()))->toBeTrue()
        ->and($usuario->canAccessPanel(panelLector()))->toBeTrue();
});

it('no deja entrar al panel al rol Cliente', function () {
    expect(usuarioConRol('Cliente')->canAccessPanel(panelAdmin()))->toBeFalse();
});

it('no deja entrar a ningun panel a un usuario sin roles', function () {
    $sinRoles = User::factory()->create();

    expect($sinRoles->canAccessPanel(panelAdmin()))->toBeFalse()
        ->and($sinRoles->canAccessPanel(panelLector()))->toBeFalse();
});

it('siembra un administrador que puede entrar al panel y tiene permisos', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', config('admin.email'))->first();

    expect($admin)->not->toBeNull()
        ->and($admin->hasRole(config('filament-shield.super_admin.name')))->toBeTrue()
        ->and($admin->canAccessPanel(panelAdmin()))->toBeTrue()
        ->and($admin->getAllPermissions()->count())->toBeGreaterThan(0);
});

it('sigue dejando entrar al administrador sembrado fuera del entorno local', function () {
    config()->set('app.env', 'production');

    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', config('admin.email'))->first();

    expect($admin->canAccessPanel(panelAdmin()))->toBeTrue();
});
