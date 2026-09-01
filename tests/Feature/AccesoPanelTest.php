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

function usuarioConRol(string $rol): User
{
    Role::findOrCreate($rol, 'web');

    return User::factory()->create()->assignRole($rol);
}

it('deja entrar al panel a los roles internos', function (string $rol) {
    expect(usuarioConRol($rol)->canAccessPanel(panelAdmin()))->toBeTrue();
})->with(['Administrador', 'Secretario', 'Operario']);

it('mantiene la lista de roles del panel alineada con la configuracion', function () {
    expect(config('admin.panel_roles'))
        ->toEqualCanonicalizing(['Administrador', 'Secretario', 'Operario'])
        ->and(config('admin.roles'))->toContain('Cliente');
});

it('deja entrar al panel al super admin', function () {
    $rol = config('filament-shield.super_admin.name');

    expect(usuarioConRol($rol)->canAccessPanel(panelAdmin()))->toBeTrue();
});

it('no deja entrar al panel al rol Cliente', function () {
    expect(usuarioConRol('Cliente')->canAccessPanel(panelAdmin()))->toBeFalse();
});

it('no deja entrar al panel a un usuario sin roles', function () {
    expect(User::factory()->create()->canAccessPanel(panelAdmin()))->toBeFalse();
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
