<?php

use App\Filament\Admin\Resources\Clientes\ClienteResource;
use App\Models\Boleta;
use App\Models\Cliente;
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

it('guarda un cliente nuevo desde el formulario', function () {
    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->fillForm([
            'codigo' => 'CLI-0001',
            'nombre' => 'María Xicay',
            'dpi' => '1234567890101',
            'estado' => 'activo',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Cliente::where('codigo', 'CLI-0001')->exists())->toBeTrue();
});

it('rechaza un codigo de cliente repetido', function () {
    Cliente::factory()->create(['codigo' => 'CLI-0001']);

    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->fillForm(['codigo' => 'CLI-0001', 'nombre' => 'Otro vecino', 'estado' => 'activo'])
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
