<?php

use App\Filament\Admin\Resources\Clientes\ClienteResource;
use App\Filament\Admin\Resources\Contadores\ContadorResource;
use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Correlativo;
use App\Models\Paja;
use App\Models\Predio;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Los códigos del padrón los propone el sistema, la oficina puede
 * sobrescribirlos, y reenviar el alta no duplica a la persona.
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

it('propone el primer codigo libre en el alta de cliente', function () {
    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->assertFormSet(['codigo' => 'CLI-0001']);
});

it('propone el primer codigo libre en el alta de contador', function () {
    Livewire::test(ContadorResource::getPages()['create']->getPage())
        ->assertFormSet(['codigo' => 'CTR-0001']);
});

it('avanza el correlativo con cada alta', function () {
    Cliente::factory()->create(['codigo' => 'CLI-0001']);

    expect(Correlativo::siguienteDisponible('cliente', Cliente::class))->toBe('CLI-0002');

    Cliente::factory()->create(['codigo' => 'CLI-0002']);

    expect(Correlativo::siguienteDisponible('cliente', Cliente::class))->toBe('CLI-0003');
});

it('no propone un codigo que ocupa un registro eliminado', function () {
    // El índice único no distingue eliminados: proponerlo otra vez chocaría.
    Cliente::factory()->create(['codigo' => 'CLI-0001'])->delete();

    expect(Correlativo::siguienteDisponible('cliente', Cliente::class))->toBe('CLI-0002');
});

it('se pone al dia cuando la oficina teclea su propio codigo', function () {
    Cliente::factory()->create(['codigo' => 'CLI-0050']);

    expect(Correlativo::siguienteDisponible('cliente', Cliente::class))->toBe('CLI-0051');
});

it('ignora los codigos viejos que no siguen el formato', function () {
    Cliente::factory()->create(['codigo' => '093190']);

    expect(Correlativo::siguienteDisponible('cliente', Cliente::class))->toBe('CLI-0001');
});

it('no retrocede el correlativo con un codigo menor al que ya iba', function () {
    Cliente::factory()->create(['codigo' => 'CLI-0050']);
    Cliente::factory()->create(['codigo' => 'CLI-0002']);

    expect(Correlativo::siguienteDisponible('cliente', Cliente::class))->toBe('CLI-0051');
});

it('da de alta al cliente con el codigo que propuso', function () {
    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->fillForm(['nombre' => 'María Xicay', 'estado' => 'activo', 'modo_predio' => 'ninguno'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Cliente::where('codigo', 'CLI-0001')->exists())->toBeTrue();
});

it('respeta el codigo que la oficina escribe encima del propuesto', function () {
    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->fillForm([
            'codigo' => 'PADRON-77',
            'nombre' => 'Josefa Tzoc',
            'estado' => 'activo',
            'modo_predio' => 'ninguno',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Cliente::where('codigo', 'PADRON-77')->exists())->toBeTrue()
        // No calza con el formato, así que el correlativo sigue donde iba.
        ->and(Correlativo::siguienteDisponible('cliente', Cliente::class))->toBe('CLI-0001');
});

it('no da de alta dos veces a la misma persona si se reenvia el formulario', function () {
    $paja = Paja::factory()->create();
    $predio = Predio::factory()->create();

    $alta = [
        'token_alta' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        'codigo' => 'CLI-0001',
        'nombre' => 'Marta Sicán',
        'estado' => 'activo',
        'modo_predio' => 'existente',
        'predio_existente_id' => $predio->id,
        'contador' => [
            'codigo' => 'CTR-0001',
            'paja_id' => $paja->id,
            'estado' => 'activo',
        ],
    ];

    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->fillForm($alta)
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Cliente::count())->toBe(1)
        ->and(Contador::count())->toBe(1);

    // El navegador reenvía exactamente el mismo formulario.
    Livewire::test(ClienteResource::getPages()['create']->getPage())
        ->fillForm($alta)
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Cliente::count())->toBe(1)
        ->and(Contador::count())->toBe(1);
});

it('deja dar de alta a dos personas distintas seguidas', function () {
    foreach (['Ana', 'Berta'] as $nombre) {
        Livewire::test(ClienteResource::getPages()['create']->getPage())
            ->fillForm(['nombre' => $nombre, 'estado' => 'activo', 'modo_predio' => 'ninguno'])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    expect(Cliente::pluck('codigo')->all())->toBe(['CLI-0001', 'CLI-0002']);
});
