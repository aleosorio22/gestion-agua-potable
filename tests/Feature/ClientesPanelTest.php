<?php

use App\Filament\Admin\Resources\Clientes\ClienteResource;
use App\Models\Boleta;
use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Paja;
use App\Models\Predio;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Las pantallas del padrón de clientes. Lo que interesa demostrar no es que
 * pinten, sino que el borrado se comporte como manda el negocio: el cliente que
 * ya respalda boletas no se elimina, y el que se eliminó por error se restaura.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->seed(ShieldSeeder::class);

    $this->actingAs(
        User::factory()->create()->assignRole(
            Role::findByName(config('filament-shield.super_admin.name'), 'web')
        )
    );
});

it('pinta el listado de clientes con datos dentro', function () {
    Cliente::factory()->count(3)->create();

    Livewire::test(ClienteResource::getPages()['index']->getPage())
        ->assertSuccessful();
});

it('pinta el formulario de alta', function () {
    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->assertSuccessful();
});

it('pinta el formulario de edicion', function () {
    $cliente = Cliente::factory()->create();

    Livewire::test(ClienteResource::getPages()['edit']->getPage(), ['record' => $cliente->getKey()])
        ->assertSuccessful();
});

it('da de alta solo a la persona cuando todavia no tiene servicio', function () {
    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->fillForm([
            'codigo' => 'CLI-0001',
            'nombre' => 'María Xicay',
            'dpi' => '1234567890101',
            'estado' => 'activo',
            'modo_predio' => 'ninguno',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $cliente = Cliente::where('codigo', 'CLI-0001')->first();

    expect($cliente)->not->toBeNull()
        ->and($cliente->contadores)->toHaveCount(0);
});

it('da de alta persona, predio y contador en una sola secuencia', function () {
    $paja = Paja::factory()->create();

    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->fillForm([
            'codigo' => 'CLI-0002',
            'nombre' => 'Josefa Tzoc',
            'estado' => 'activo',
            'modo_predio' => 'nuevo',
            'predio' => [
                'aldea' => 'El Porvenir',
                'numero_casa' => '1-31',
            ],
            'contador' => [
                'codigo' => 'CTR-00500',
                'paja_id' => $paja->id,
                'estado' => 'activo',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $cliente = Cliente::where('codigo', 'CLI-0002')->first();
    $contador = $cliente->contadores()->first();

    expect($contador->codigo)->toBe('CTR-00500')
        ->and($contador->predio->aldea)->toBe('El Porvenir')
        ->and($contador->predio->numero_casa)->toBe('1-31');
});

it('reusa una propiedad ya registrada en lugar de duplicarla', function () {
    $predio = Predio::factory()->create();
    $paja = Paja::factory()->create();
    $prediosAntes = Predio::count();

    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->fillForm([
            'codigo' => 'CLI-0003',
            'nombre' => 'Marta Sicán',
            'estado' => 'activo',
            'modo_predio' => 'existente',
            'predio_existente_id' => $predio->id,
            'contador' => [
                'codigo' => 'CTR-00600',
                'paja_id' => $paja->id,
                'estado' => 'activo',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Predio::count())->toBe($prediosAntes)
        ->and(Contador::where('codigo', 'CTR-00600')->first()->predio_id)->toBe($predio->id);
});

it('no deja al cliente creado si el contador viene con un codigo repetido', function () {
    $existente = Contador::factory()->create(['codigo' => 'CTR-REPETIDO']);
    $paja = Paja::factory()->create();

    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->fillForm([
            'codigo' => 'CLI-0004',
            'nombre' => 'Pedro Cúmez',
            'estado' => 'activo',
            'modo_predio' => 'nuevo',
            'predio' => ['aldea' => 'San Antonio'],
            'contador' => [
                'codigo' => 'CTR-REPETIDO',
                'paja_id' => $paja->id,
                'estado' => 'activo',
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['contador.codigo']);

    // Nada se escribe hasta el último paso: el cliente no quedó a medias.
    expect(Cliente::where('codigo', 'CLI-0004')->exists())->toBeFalse();
});

it('rechaza un codigo de cliente repetido', function () {
    Cliente::factory()->create(['codigo' => 'CLI-0001']);

    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->fillForm(['codigo' => 'CLI-0001', 'nombre' => 'Otro vecino', 'estado' => 'activo', 'modo_predio' => 'ninguno'])
        ->call('create')
        ->assertHasFormErrors(['codigo']);
});

it('rechaza un dpi que ya tiene otro cliente', function () {
    Cliente::factory()->create(['dpi' => '1234567890101']);

    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->fillForm([
            'codigo' => 'CLI-0002',
            'nombre' => 'Otro vecino',
            'dpi' => '1234567890101',
            'estado' => 'activo',
            'modo_predio' => 'ninguno',
        ])
        ->call('create')
        ->assertHasFormErrors(['dpi']);
});

it('rechaza un dpi que no tiene 13 digitos', function () {
    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->fillForm([
            'codigo' => 'CLI-0003',
            'nombre' => 'Vecino con DPI corto',
            'dpi' => '12345',
            'estado' => 'activo',
            'modo_predio' => 'ninguno',
        ])
        ->call('create')
        ->assertHasFormErrors(['dpi']);
});

it('deshabilita el borrado de un cliente con boletas y lo deja en uno libre', function () {
    $conBoletas = Boleta::factory()->create()->cliente;
    $libre = Cliente::factory()->create();

    Livewire::test(ClienteResource::getPages()['index']->getPage())
        ->assertTableActionDisabled('delete', $conBoletas)
        ->assertTableActionEnabled('delete', $libre);
});

it('da de baja logica al cliente libre, sin sacarlo de la base', function () {
    $cliente = Cliente::factory()->create();

    Livewire::test(ClienteResource::getPages()['index']->getPage())
        ->callTableAction('delete', $cliente);

    expect(Cliente::withTrashed()->whereKey($cliente->getKey())->exists())->toBeTrue()
        ->and($cliente->fresh()->trashed())->toBeTrue();
});

it('deja abrir y restaurar un cliente eliminado', function () {
    $cliente = Cliente::factory()->create();
    $cliente->delete();

    Livewire::test(ClienteResource::getPages()['edit']->getPage(), ['record' => $cliente->getKey()])
        ->assertSuccessful()
        ->callAction('restore');

    expect($cliente->fresh()->trashed())->toBeFalse();
});

it('no permite el borrado definitivo ni al super admin', function () {
    $cliente = Cliente::factory()->create();

    expect(auth()->user()->can('forceDelete', $cliente))->toBeFalse();
});
