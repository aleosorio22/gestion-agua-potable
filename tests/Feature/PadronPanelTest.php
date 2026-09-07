<?php

use App\Filament\Admin\Resources\Clientes\ClienteResource;
use App\Filament\Admin\Resources\Clientes\RelationManagers\ContadoresRelationManager;
use App\Filament\Admin\Resources\Contadores\ContadorResource;
use App\Filament\Admin\Resources\Predios\PredioResource;
use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Paja;
use App\Models\Predio;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Las pantallas de predios y contadores: el eslabón entre la persona y el
 * medidor que después leerá el lector en campo.
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

it('pinta el listado de predios y de contadores', function (string $resource, callable $sembrar) {
    $sembrar();

    Livewire::test($resource::getPages()['index']->getPage())
        ->assertSuccessful();
})->with([
    'predios' => [PredioResource::class, fn () => Predio::factory()->count(3)->create()],
    'contadores' => [ContadorResource::class, fn () => Contador::factory()->count(3)->create()],
]);

it('pinta el formulario de alta de predios y de contadores', function (string $resource) {
    Livewire::test($resource::getPages()['create']->getPage())
        ->assertSuccessful();
})->with([
    'predios' => PredioResource::class,
    'contadores' => ContadorResource::class,
]);

it('pinta el formulario de edicion de predios y de contadores', function (string $resource, callable $registro) {
    Livewire::test($resource::getPages()['edit']->getPage(), ['record' => $registro()->getKey()])
        ->assertSuccessful();
})->with([
    'predios' => [PredioResource::class, fn () => Predio::factory()->create()],
    'contadores' => [ContadorResource::class, fn () => Contador::factory()->create()],
]);

it('guarda un contador nuevo desde el formulario', function () {
    $cliente = Cliente::factory()->create();
    $predio = Predio::factory()->create();
    $paja = Paja::factory()->create();

    Livewire::test(ContadorResource::getPages()['create']->getPage())
        ->fillForm([
            'codigo' => 'CTR-00123',
            'cliente_id' => $cliente->id,
            'predio_id' => $predio->id,
            'paja_id' => $paja->id,
            'estado' => 'activo',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Contador::where('codigo', 'CTR-00123')->exists())->toBeTrue();
});

it('rechaza un codigo de contador repetido', function () {
    $existente = Contador::factory()->create(['codigo' => 'CTR-00123']);

    Livewire::test(ContadorResource::getPages()['create']->getPage())
        ->fillForm([
            'codigo' => 'CTR-00123',
            'cliente_id' => $existente->cliente_id,
            'predio_id' => $existente->predio_id,
            'paja_id' => $existente->paja_id,
            'estado' => 'activo',
        ])
        ->call('create')
        ->assertHasFormErrors(['codigo']);
});

it('rechaza una fecha de instalacion futura', function () {
    $contador = Contador::factory()->create();

    Livewire::test(ContadorResource::getPages()['edit']->getPage(), ['record' => $contador->getKey()])
        ->fillForm(['fecha_instalacion' => now()->addMonth()->toDateString()])
        ->call('save')
        ->assertHasFormErrors(['fecha_instalacion']);
});

it('deshabilita el borrado de un contador con lecturas y lo deja en uno libre', function () {
    $conLecturas = Lectura::factory()->create()->contador;
    $libre = Contador::factory()->create();

    Livewire::test(ContadorResource::getPages()['index']->getPage())
        ->assertTableActionDisabled('delete', $conLecturas)
        ->assertTableActionEnabled('delete', $libre);
});

it('deshabilita el borrado de un predio con contador instalado', function () {
    $conContador = Contador::factory()->create()->predio;
    $libre = Predio::factory()->create();

    Livewire::test(PredioResource::getPages()['index']->getPage())
        ->assertTableActionDisabled('delete', $conContador)
        ->assertTableActionEnabled('delete', $libre);
});

it('no permite el borrado definitivo de contadores ni de predios', function () {
    expect(auth()->user()->can('forceDelete', Contador::factory()->create()))->toBeFalse()
        ->and(auth()->user()->can('forceDelete', Predio::factory()->create()))->toBeFalse();
});

it('lista los contadores del cliente dentro de su ficha', function () {
    $cliente = Cliente::factory()->create();
    $suyo = Contador::factory()->create(['cliente_id' => $cliente->id, 'codigo' => 'CTR-PROPIO']);
    $ajeno = Contador::factory()->create(['codigo' => 'CTR-AJENO']);

    Livewire::test(ContadoresRelationManager::class, [
        'ownerRecord' => $cliente,
        'pageClass' => ClienteResource::getPages()['edit']->getPage(),
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$suyo])
        ->assertCanNotSeeTableRecords([$ajeno]);
});

it('agrega un contador al cliente desde su ficha, sin preguntar el titular', function () {
    $cliente = Cliente::factory()->create();
    $predio = Predio::factory()->create();
    $paja = Paja::factory()->create();

    Livewire::test(ContadoresRelationManager::class, [
        'ownerRecord' => $cliente,
        'pageClass' => ClienteResource::getPages()['edit']->getPage(),
    ])
        ->callTableAction('create', data: [
            'codigo' => 'CTR-DESDE-FICHA',
            'predio_id' => $predio->id,
            'paja_id' => $paja->id,
            'estado' => 'activo',
        ])
        ->assertHasNoTableActionErrors();

    expect(Contador::where('codigo', 'CTR-DESDE-FICHA')->first()?->cliente_id)->toBe($cliente->id);
});

it('encuentra al titular buscando por el codigo del contador', function () {
    $sector = Sector::factory()->create();
    $predio = Predio::factory()->create(['sector_id' => $sector->id]);
    $contador = Contador::factory()->create(['predio_id' => $predio->id, 'codigo' => 'CTR-BUSCADO']);

    $resultados = ContadorResource::getGlobalSearchResults('CTR-BUSCADO');

    expect($resultados)->toHaveCount(1)
        ->and($resultados->first()->details['Titular'])->toBe($contador->cliente->nombre);
});
