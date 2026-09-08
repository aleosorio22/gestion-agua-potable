<?php

use App\Filament\Admin\Resources\Lecturas\LecturaResource;
use App\Filament\Admin\Resources\Periodos\PeriodoResource;
use App\Models\Boleta;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Periodo;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Las pantallas del ciclo mensual: abrir el período, registrar las lecturas y
 * cerrar el mes. Es donde las invariantes del observer tienen que llegar al
 * usuario como validación en español y no como una excepción.
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

it('pinta el listado de periodos', function () {
    Periodo::factory()->count(3)->create();

    Livewire::test(PeriodoResource::getPages()['index']->getPage())
        ->assertSuccessful();
});

it('propone el mes siguiente al ultimo periodo al abrir uno nuevo', function () {
    Periodo::factory()->create(['anio' => 2026, 'mes' => 9]);

    expect(Periodo::siguienteSugerido())->toMatchArray([
        'anio' => 2026,
        'mes' => 10,
        'fecha_inicio' => '2026-10-01',
        'fecha_fin' => '2026-10-31',
    ]);

    Livewire::test(PeriodoResource::getPages()['create']->getPage())
        ->assertSuccessful()
        ->assertFormSet(['anio' => 2026, 'mes' => 10]);
});

it('cruza de anio al proponer el periodo siguiente a diciembre', function () {
    Periodo::factory()->create(['anio' => 2026, 'mes' => 12]);

    expect(Periodo::siguienteSugerido())->toMatchArray(['anio' => 2027, 'mes' => 1]);
});

it('abre un periodo nuevo desde el formulario', function () {
    Livewire::test(PeriodoResource::getPages()['create']->getPage())
        ->fillForm([
            'anio' => 2027,
            'mes' => 3,
            'fecha_inicio' => '2027-03-01',
            'fecha_fin' => '2027-03-31',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Periodo::where(['anio' => 2027, 'mes' => 3])->exists())->toBeTrue();
});

it('rechaza dos periodos del mismo mes y anio', function () {
    Periodo::factory()->create(['anio' => 2026, 'mes' => 9]);

    Livewire::test(PeriodoResource::getPages()['create']->getPage())
        ->fillForm([
            'anio' => 2026,
            'mes' => 9,
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-09-30',
        ])
        ->call('create')
        ->assertHasFormErrors(['mes']);
});

it('acepta el mismo mes en otro anio', function () {
    Periodo::factory()->create(['anio' => 2026, 'mes' => 9]);

    Livewire::test(PeriodoResource::getPages()['create']->getPage())
        ->fillForm([
            'anio' => 2027,
            'mes' => 9,
            'fecha_inicio' => '2027-09-01',
            'fecha_fin' => '2027-09-30',
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('rechaza un rango de fechas invertido', function () {
    Livewire::test(PeriodoResource::getPages()['create']->getPage())
        ->fillForm([
            'anio' => 2027,
            'mes' => 5,
            'fecha_inicio' => '2027-05-31',
            'fecha_fin' => '2027-05-01',
        ])
        ->call('create')
        ->assertHasFormErrors(['fecha_fin']);
});

it('cierra un periodo y registra quien lo cerro', function () {
    $periodo = Periodo::factory()->create();

    Livewire::test(PeriodoResource::getPages()['index']->getPage())
        ->callTableAction('cerrar', $periodo);

    $periodo->refresh();

    expect($periodo->esta_cerrado)->toBeTrue()
        ->and($periodo->cerrado_por)->toBe(auth()->id());
});

it('deja el formulario de un periodo cerrado en solo lectura', function () {
    $periodo = Periodo::factory()->create();
    $periodo->cerrar(auth()->user());

    Livewire::test(PeriodoResource::getPages()['edit']->getPage(), ['record' => $periodo->getKey()])
        ->assertSuccessful()
        ->assertFormFieldDisabled('mes')
        ->assertFormFieldDisabled('fecha_inicio');
});

it('no ofrece cerrar dos veces el mismo periodo', function () {
    $periodo = Periodo::factory()->create();
    $periodo->cerrar(auth()->user());

    Livewire::test(PeriodoResource::getPages()['index']->getPage())
        ->assertTableActionHidden('cerrar', $periodo);
});

it('deshabilita el borrado de un periodo con lecturas y lo deja en uno vacio', function () {
    $conLecturas = Lectura::factory()->create()->periodo;
    $vacio = Periodo::factory()->create();

    Livewire::test(PeriodoResource::getPages()['index']->getPage())
        ->assertTableActionDisabled('delete', $conLecturas)
        ->assertTableActionEnabled('delete', $vacio);
});

it('no ofrece borrar un periodo cerrado', function () {
    $periodo = Periodo::factory()->create();
    $periodo->cerrar(auth()->user());

    Livewire::test(PeriodoResource::getPages()['index']->getPage())
        ->assertTableActionHidden('delete', $periodo);
});

it('cuenta las boletas emitidas en el periodo', function () {
    $boleta = Boleta::factory()->create();

    expect($boleta->periodo->boletas()->count())->toBe(1);
});

it('pinta el listado y el formulario de lecturas', function () {
    Lectura::factory()->count(3)->create();

    Livewire::test(LecturaResource::getPages()['index']->getPage())
        ->assertSuccessful();

    Livewire::test(LecturaResource::getPages()['create']->getPage())
        ->assertSuccessful();
});

it('precarga la lectura anterior al elegir el contador', function () {
    $periodo = Periodo::factory()->create();
    $anterior = Lectura::factory()->create(['lectura_actual' => 42.5]);

    Livewire::test(LecturaResource::getPages()['create']->getPage())
        ->fillForm([
            'periodo_id' => $periodo->id,
            'contador_id' => $anterior->contador_id,
        ])
        ->assertFormSet(['lectura_anterior' => 42.5]);
});

it('arranca en cero el contador que nunca fue leido', function () {
    $periodo = Periodo::factory()->create();
    $contador = Contador::factory()->create();

    Livewire::test(LecturaResource::getPages()['create']->getPage())
        ->fillForm([
            'periodo_id' => $periodo->id,
            'contador_id' => $contador->id,
        ])
        ->assertFormSet(['lectura_anterior' => 0]);
});

it('registra una lectura y le adjudica el consumo y el lector', function () {
    $periodo = Periodo::factory()->create();
    $contador = Contador::factory()->create();

    Livewire::test(LecturaResource::getPages()['create']->getPage())
        ->fillForm([
            'periodo_id' => $periodo->id,
            'contador_id' => $contador->id,
            'fecha_lectura' => now()->toDateString(),
            'lectura_actual' => 18.75,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $lectura = Lectura::where('contador_id', $contador->id)->first();

    expect((float) $lectura->consumo_m3)->toBe(18.75)
        ->and($lectura->usuario_id)->toBe(auth()->id());
});

it('rechaza una lectura menor que la anterior sin dejar que reviente el observer', function () {
    $periodo = Periodo::factory()->create();
    $anterior = Lectura::factory()->create(['lectura_actual' => 50]);

    Livewire::test(LecturaResource::getPages()['create']->getPage())
        ->fillForm([
            'periodo_id' => $periodo->id,
            'contador_id' => $anterior->contador_id,
            'fecha_lectura' => now()->toDateString(),
            'lectura_actual' => 30,
        ])
        ->call('create')
        ->assertHasFormErrors(['lectura_actual']);
});

it('rechaza leer dos veces el mismo contador en un periodo', function () {
    $ya = Lectura::factory()->create();

    Livewire::test(LecturaResource::getPages()['create']->getPage())
        ->fillForm([
            'periodo_id' => $ya->periodo_id,
            'contador_id' => $ya->contador_id,
            'fecha_lectura' => now()->toDateString(),
            'lectura_actual' => (float) $ya->lectura_actual + 10,
        ])
        ->call('create')
        ->assertHasFormErrors(['contador_id']);
});

it('acepta el mismo contador en otro periodo', function () {
    $ya = Lectura::factory()->create();
    $otro = Periodo::factory()->create(['anio' => 2027, 'mes' => 6]);

    Livewire::test(LecturaResource::getPages()['create']->getPage())
        ->fillForm([
            'periodo_id' => $otro->id,
            'contador_id' => $ya->contador_id,
            'fecha_lectura' => now()->toDateString(),
            'lectura_actual' => (float) $ya->lectura_actual + 10,
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('rechaza una fecha de visita futura', function () {
    $periodo = Periodo::factory()->create();
    $contador = Contador::factory()->create();

    Livewire::test(LecturaResource::getPages()['create']->getPage())
        ->fillForm([
            'periodo_id' => $periodo->id,
            'contador_id' => $contador->id,
            'fecha_lectura' => now()->addWeek()->toDateString(),
            'lectura_actual' => 10,
        ])
        ->call('create')
        ->assertHasFormErrors(['fecha_lectura']);
});

it('no ofrece periodos cerrados al registrar una lectura', function () {
    $abierto = Periodo::factory()->create(['anio' => 2027, 'mes' => 1]);
    $cerrado = Periodo::factory()->create(['anio' => 2027, 'mes' => 2]);
    $cerrado->cerrar(auth()->user());

    $opciones = Livewire::test(LecturaResource::getPages()['create']->getPage())
        ->instance()
        ->form
        ->getComponent('periodo_id')
        ->getOptions();

    expect($opciones)->toHaveKey($abierto->id)
        ->and($opciones)->not->toHaveKey($cerrado->id);
});

it('apaga el alta de lecturas cuando no hay ningun periodo abierto', function () {
    Periodo::factory()->create()->cerrar(auth()->user());

    expect(LecturaResource::canCreate())->toBeFalse();

    Periodo::factory()->create(['anio' => 2027, 'mes' => 4]);

    expect(LecturaResource::canCreate())->toBeTrue();
});

it('bloquea la correccion y el borrado de una lectura ya facturada', function () {
    $facturada = Boleta::factory()->create()->lectura;
    $libre = Lectura::factory()->create();

    Livewire::test(LecturaResource::getPages()['index']->getPage())
        ->filterTable('periodo_id', null)
        ->assertTableActionDisabled('edit', $facturada)
        ->assertTableActionDisabled('delete', $facturada)
        ->assertTableActionEnabled('edit', $libre)
        ->assertTableActionEnabled('delete', $libre);

    Livewire::test(LecturaResource::getPages()['edit']->getPage(), ['record' => $facturada->getKey()])
        ->assertFormFieldDisabled('lectura_actual');
});
