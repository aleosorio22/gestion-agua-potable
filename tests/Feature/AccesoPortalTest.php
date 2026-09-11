<?php

use App\Filament\Admin\Resources\Clientes\ClienteResource;
use App\Filament\Admin\Resources\Clientes\RelationManagers\AccesosPortalRelationManager;
use App\Filament\Admin\Resources\Usuarios\UsuarioResource;
use App\Models\Cliente;
use App\Models\ClienteAcceso;
use App\Models\User;
use App\Services\AccesoAlPortal;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * El acceso del vecino al portal de autoservicio.
 *
 * Otorgarlo toca tres cosas —cuenta, rol y registro— y `canAccessPanel()` exige
 * las tres; lo que se prueba acá es que vayan juntas, que revocar no destruya
 * nada, y que se pueda volver a otorgar, que es lo que el esquema original
 * impedía.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->seed(ShieldSeeder::class);
    $this->seed(RoleSeeder::class);

    $this->actingAs(
        User::factory()->create()->assignRole(
            Role::findByName(config('filament-shield.super_admin.name'), 'web')
        )
    );

    $this->cliente = Cliente::factory()->create([
        'nombre' => 'María Xicay',
        'email' => 'maria@vecina.test',
    ]);
});

function fichaDelCliente(Cliente $cliente): Testable
{
    return Livewire::test(AccesosPortalRelationManager::class, [
        'ownerRecord' => $cliente,
        'pageClass' => ClienteResource::getPages()['edit']->getPage(),
    ]);
}

it('otorga acceso creando la cuenta, el rol y el registro', function () {
    $acceso = app(AccesoAlPortal::class)->otorgar(
        $this->cliente,
        'maria@vecina.test',
        'María Xicay',
        'clave-inicial-1',
    );

    $usuario = $acceso->user;

    expect($usuario->hasRole('Cliente'))->toBeTrue()
        ->and($acceso->otorgado_por)->toBe(auth()->id())
        ->and($acceso->revocado_en)->toBeNull()
        ->and(Hash::check('clave-inicial-1', $usuario->password))->toBeTrue()
        ->and($usuario->canAccessPanel(Filament::getPanel('portal')))->toBeTrue();
});

it('deja entrar al portal solo con cuenta y acceso vigente', function () {
    $sinAcceso = User::factory()->create()->assignRole(Role::findByName('Cliente', 'web'));

    // El rol solo no abre nada: canAccessPanel exige también el acceso.
    expect($sinAcceso->canAccessPanel(Filament::getPanel('portal')))->toBeFalse();
});

it('vincula una cuenta que ya existe en vez de duplicarla', function () {
    $existente = User::factory()->create([
        'email' => 'maria@vecina.test',
        'password' => Hash::make('la-que-ya-tenia'),
    ]);

    $acceso = app(AccesoAlPortal::class)->otorgar($this->cliente, 'maria@vecina.test');

    expect(User::where('email', 'maria@vecina.test')->count())->toBe(1)
        ->and($acceso->user_id)->toBe($existente->id)
        ->and(Hash::check('la-que-ya-tenia', $existente->fresh()->password))->toBeTrue();
});

it('no deja dos accesos vigentes para el mismo cliente', function () {
    app(AccesoAlPortal::class)->otorgar($this->cliente, 'maria@vecina.test', 'María', 'clave-inicial-1');

    app(AccesoAlPortal::class)->otorgar($this->cliente, 'otra@vecina.test', 'Otra', 'clave-inicial-2');
})->throws(RuntimeException::class, 'ya tiene acceso vigente');

it('no deja que una cuenta consulte a dos clientes', function () {
    app(AccesoAlPortal::class)->otorgar($this->cliente, 'maria@vecina.test', 'María', 'clave-inicial-1');

    $otroCliente = Cliente::factory()->create();

    app(AccesoAlPortal::class)->otorgar($otroCliente, 'maria@vecina.test');
})->throws(RuntimeException::class, 'atiende a un solo cliente');

it('revoca sin borrar el historial y deja fuera del portal', function () {
    $acceso = app(AccesoAlPortal::class)->otorgar($this->cliente, 'maria@vecina.test', 'María', 'clave-inicial-1');
    $usuario = $acceso->user;

    app(AccesoAlPortal::class)->revocar($acceso);

    $acceso->refresh();

    expect($acceso->revocado_en)->not->toBeNull()
        ->and($acceso->revocado_por)->toBe(auth()->id())
        ->and(ClienteAcceso::count())->toBe(1)
        ->and(User::whereKey($usuario->id)->exists())->toBeTrue()
        ->and($usuario->fresh()->canAccessPanel(Filament::getPanel('portal')))->toBeFalse();
});

it('permite volver a otorgar el acceso despues de revocarlo', function () {
    // Es lo que el esquema original impedía: el unique iba sobre cliente_id a
    // secas, así que había una sola fila por cliente en toda la historia.
    $primero = app(AccesoAlPortal::class)->otorgar($this->cliente, 'maria@vecina.test', 'María', 'clave-inicial-1');
    app(AccesoAlPortal::class)->revocar($primero);

    $segundo = app(AccesoAlPortal::class)->otorgar($this->cliente, 'hija@vecina.test', 'Hija de María', 'clave-inicial-2');

    expect(ClienteAcceso::count())->toBe(2)
        ->and($segundo->revocado_en)->toBeNull()
        ->and($this->cliente->accesoActivo()->first()->id)->toBe($segundo->id);
});

it('otorga el acceso desde la ficha del cliente', function () {
    fichaDelCliente($this->cliente)
        ->assertSuccessful()
        ->callTableAction('otorgar', data: [
            'email' => 'maria@vecina.test',
            'name' => 'María Xicay',
            'password' => 'clave-inicial-1',
        ])
        ->assertHasNoTableActionErrors();

    expect($this->cliente->accesoActivo()->exists())->toBeTrue();
});

it('esconde el boton de otorgar cuando ya hay acceso vigente', function () {
    app(AccesoAlPortal::class)->otorgar($this->cliente, 'maria@vecina.test', 'María', 'clave-inicial-1');

    fichaDelCliente($this->cliente)->assertTableActionHidden('otorgar');
});

it('revoca desde la ficha y vuelve a ofrecer otorgar', function () {
    $acceso = app(AccesoAlPortal::class)->otorgar($this->cliente, 'maria@vecina.test', 'María', 'clave-inicial-1');

    fichaDelCliente($this->cliente)
        ->callTableAction('revocar', $acceso)
        ->assertTableActionVisible('otorgar');

    expect($acceso->fresh()->revocado_en)->not->toBeNull();
});

it('restablece la contraseña del vecino desde su ficha', function () {
    $acceso = app(AccesoAlPortal::class)->otorgar($this->cliente, 'maria@vecina.test', 'María', 'clave-inicial-1');

    fichaDelCliente($this->cliente)
        ->callTableAction('restablecerContrasenaPortal', $acceso, data: [
            'password' => 'clave-nueva-1',
            'password_confirmation' => 'clave-nueva-1',
        ]);

    expect(Hash::check('clave-nueva-1', $acceso->user->fresh()->password))->toBeTrue();
});

it('deja las cuentas del portal fuera de la pantalla de usuarios', function () {
    $acceso = app(AccesoAlPortal::class)->otorgar($this->cliente, 'maria@vecina.test', 'María', 'clave-inicial-1');
    $personal = User::factory()->create()->assignRole(Role::findByName('Secretaria', 'web'));

    // Listarlas con el personal invitaría a asignarles un rol de oficina.
    Livewire::test(UsuarioResource::getPages()['index']->getPage())
        ->assertCanSeeTableRecords([$personal])
        ->assertCanNotSeeTableRecords([$acceso->user]);
});

it('devuelve la cuenta del portal al listado de usuarios si se le revoca', function () {
    $acceso = app(AccesoAlPortal::class)->otorgar($this->cliente, 'maria@vecina.test', 'María', 'clave-inicial-1');

    app(AccesoAlPortal::class)->revocar($acceso);

    Livewire::test(UsuarioResource::getPages()['index']->getPage())
        ->assertCanSeeTableRecords([$acceso->user->fresh()]);
});
