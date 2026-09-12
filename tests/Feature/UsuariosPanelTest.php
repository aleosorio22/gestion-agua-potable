<?php

use App\Filament\Admin\Resources\Usuarios\UsuarioResource;
use App\Filament\Admin\Support\CandadosDeUsuario;
use App\Models\Lectura;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * La gestión de cuentas.
 *
 * Lo que más importa demostrar son los candados: que nadie pueda dejar a la
 * oficina encerrada afuera de su propio sistema, ni borrar a quien ya cargó
 * trabajo con su nombre.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->seed(ShieldSeeder::class);
    $this->seed(RoleSeeder::class);

    $this->rolAdmin = config('filament-shield.super_admin.name');

    $this->yo = User::factory()->create(['name' => 'Administrador'])
        ->assignRole(Role::findByName($this->rolAdmin, 'web'));

    $this->actingAs($this->yo);
});

/** Otro administrador, para que los candados del «último» no se disparen. */
function otroAdministrador(): User
{
    return User::factory()->create()->assignRole(
        Role::findByName(config('filament-shield.super_admin.name'), 'web')
    );
}

it('pinta el listado y los formularios de usuarios', function () {
    User::factory()->count(2)->create();

    Livewire::test(UsuarioResource::getPages()['index']->getPage())->assertSuccessful();
    Livewire::test(UsuarioResource::getPages()['create']->getPage())->assertSuccessful();
});

it('crea una cuenta con su rol y su contraseña', function () {
    $lector = Role::findByName('Lector', 'web');

    Livewire::test(UsuarioResource::getPages()['create']->getPage())
        ->fillForm([
            'name' => 'Juana Pérez',
            'email' => 'juana@oficina-agua.test',
            'password' => 'clave-segura-1',
            'password_confirmation' => 'clave-segura-1',
            'roles' => [$lector->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $usuario = User::where('email', 'juana@oficina-agua.test')->first();

    expect($usuario->hasRole('Lector'))->toBeTrue()
        ->and($usuario->activo)->toBeTrue()
        ->and(Hash::check('clave-segura-1', $usuario->password))->toBeTrue();
});

it('rechaza una contraseña que no coincide con su confirmacion', function () {
    Livewire::test(UsuarioResource::getPages()['create']->getPage())
        ->fillForm([
            'name' => 'Juana Pérez',
            'email' => 'juana@oficina-agua.test',
            'password' => 'clave-segura-1',
            'password_confirmation' => 'otra-distinta',
        ])
        ->call('create')
        ->assertHasFormErrors(['password']);

    expect(User::where('email', 'juana@oficina-agua.test')->exists())->toBeFalse();
});

it('rechaza un correo repetido', function () {
    User::factory()->create(['email' => 'repetido@oficina-agua.test']);

    Livewire::test(UsuarioResource::getPages()['create']->getPage())
        ->fillForm([
            'name' => 'Otro',
            'email' => 'repetido@oficina-agua.test',
            'password' => 'clave-segura-1',
            'password_confirmation' => 'clave-segura-1',
        ])
        ->call('create')
        ->assertHasFormErrors(['email']);
});

it('conserva la contraseña al editar sin tocarla', function () {
    $usuario = User::factory()->create(['password' => Hash::make('la-de-siempre')]);

    Livewire::test(UsuarioResource::getPages()['edit']->getPage(), ['record' => $usuario->getKey()])
        ->fillForm(['name' => 'Nombre corregido'])
        ->call('save')
        ->assertHasNoFormErrors();

    $usuario->refresh();

    // Guardar sin escribir nada en el campo no puede dejar a la persona sin
    // poder entrar: vacío significa «no la cambies».
    expect($usuario->name)->toBe('Nombre corregido')
        ->and(Hash::check('la-de-siempre', $usuario->password))->toBeTrue();
});

it('restablece la contraseña desde su accion', function () {
    $usuario = User::factory()->create(['password' => Hash::make('olvidada')]);

    Livewire::test(UsuarioResource::getPages()['index']->getPage())
        ->callTableAction('restablecerContrasena', $usuario, data: [
            'password' => 'clave-nueva-1',
            'password_confirmation' => 'clave-nueva-1',
        ]);

    expect(Hash::check('clave-nueva-1', $usuario->fresh()->password))->toBeTrue();
});

it('no ofrece el rol Cliente al crear una cuenta de oficina', function () {
    $opciones = Livewire::test(UsuarioResource::getPages()['create']->getPage())
        ->instance()
        ->form
        ->getComponent('roles')
        ->getOptions();

    // El acceso de un vecino se otorga desde cliente_accesos: darle el rol acá
    // crearía una cuenta que no entra a ningún lado.
    expect($opciones)->not->toContain('Cliente');
});

it('da de baja y reactiva una cuenta sin perder sus roles', function () {
    otroAdministrador();
    $usuario = User::factory()->create()->assignRole(Role::findByName('Secretaria', 'web'));

    Livewire::test(UsuarioResource::getPages()['index']->getPage())
        ->callTableAction('alternarActivo', $usuario);

    expect($usuario->fresh()->activo)->toBeFalse()
        ->and($usuario->fresh()->hasRole('Secretaria'))->toBeTrue();

    Livewire::test(UsuarioResource::getPages()['index']->getPage())
        ->callTableAction('alternarActivo', $usuario);

    expect($usuario->fresh()->activo)->toBeTrue();
});

it('deja fuera de todos los paneles a una cuenta dada de baja', function () {
    $usuario = User::factory()->create(['activo' => false])
        ->assignRole(Role::findByName('Secretaria', 'web'));

    expect($usuario->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('no deja que alguien se desactive a si mismo', function () {
    otroAdministrador();

    expect(CandadosDeUsuario::motivoParaNoDesactivar($this->yo))
        ->toMatch('/su propia cuenta/');

    Livewire::test(UsuarioResource::getPages()['index']->getPage())
        ->assertTableActionDisabled('alternarActivo', $this->yo);

    expect($this->yo->fresh()->activo)->toBeTrue();
});

it('no deja dar de baja al ultimo administrador activo', function () {
    $solitario = User::factory()->create()->assignRole(Role::findByName($this->rolAdmin, 'web'));
    $this->yo->update(['activo' => false]);

    expect(CandadosDeUsuario::esElUltimoAdministrador($solitario))->toBeTrue()
        ->and(CandadosDeUsuario::motivoParaNoDesactivar($solitario))
        ->toMatch('/único administrador activo/');
});

it('deja dar de baja a un administrador si queda otro activo', function () {
    $otro = otroAdministrador();

    expect(CandadosDeUsuario::esElUltimoAdministrador($otro))->toBeFalse()
        ->and(CandadosDeUsuario::motivoParaNoDesactivar($otro))->toBeNull();
});

it('no deja eliminar a quien ya registro trabajo', function () {
    $lector = Lectura::factory()->create()->usuario;

    expect(CandadosDeUsuario::motivoParaNoEliminar($lector))
        ->toMatch('/lectura tomada/');

    Livewire::test(UsuarioResource::getPages()['index']->getPage())
        ->assertTableActionDisabled('delete', $lector);
});

it('no deja que alguien se elimine a si mismo', function () {
    otroAdministrador();

    Livewire::test(UsuarioResource::getPages()['index']->getPage())
        ->assertTableActionDisabled('delete', $this->yo);
});

it('deja eliminar una cuenta recien creada que nunca trabajo', function () {
    $recienCreado = User::factory()->create();

    expect(CandadosDeUsuario::motivoParaNoEliminar($recienCreado))->toBeNull();

    Livewire::test(UsuarioResource::getPages()['index']->getPage())
        ->callTableAction('delete', $recienCreado);

    expect(User::whereKey($recienCreado->getKey())->exists())->toBeFalse();
});
