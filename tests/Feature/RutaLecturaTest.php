<?php

use App\Filament\Admin\Pages\RutaLectura;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Periodo;
use App\Models\Predio;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * La pantalla del lector en campo: qué le queda por visitar del período y el
 * registro reducido a teclear una cifra.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->seed(ShieldSeeder::class);

    $this->actingAs(
        User::factory()->create()->assignRole(
            Role::findByName(config('filament-shield.super_admin.name'), 'web')
        )
    );

    // El período por defecto es el mes en curso, así que hoy cae dentro y la
    // ruta admite registrar.
    $this->periodo = Periodo::factory()->create();
});

it('lista los contadores que faltan por leer en el periodo', function () {
    $pendiente = Contador::factory()->create();
    $yaLeido = Lectura::factory()->create(['periodo_id' => $this->periodo->id])->contador;

    Livewire::test(RutaLectura::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$pendiente])
        ->assertCanNotSeeTableRecords([$yaLeido]);
});

it('deja fuera de la ruta a los contadores inactivos y dañados', function () {
    $activo = Contador::factory()->create();
    $inactivo = Contador::factory()->create(['estado' => 'inactivo']);
    $dañado = Contador::factory()->create(['estado' => 'dañado']);

    Livewire::test(RutaLectura::class)
        ->assertCanSeeTableRecords([$activo])
        ->assertCanNotSeeTableRecords([$inactivo, $dañado]);
});

it('ordena la ruta por el orden de recorrido del sector', function () {
    $ultimo = Contador::factory()->create([
        'predio_id' => Predio::factory()->create([
            'sector_id' => Sector::factory()->create(['orden' => 9])->id,
        ])->id,
    ]);
    $primero = Contador::factory()->create([
        'predio_id' => Predio::factory()->create([
            'sector_id' => Sector::factory()->create(['orden' => 1])->id,
        ])->id,
    ]);

    Livewire::test(RutaLectura::class)
        ->assertCanSeeTableRecords([$primero, $ultimo], inOrder: true);
});

it('cuenta el avance del recorrido', function () {
    Contador::factory()->count(3)->create();
    Lectura::factory()->create(['periodo_id' => $this->periodo->id]);

    expect(Livewire::test(RutaLectura::class)->instance()->avance)
        ->toBe(['leidos' => 1, 'total' => 4]);
});

it('registra la lectura desde el modal con el lector y la fecha de hoy', function () {
    $contador = Contador::factory()->create();

    Livewire::test(RutaLectura::class)
        ->callTableAction('registrar', $contador, data: [
            'lectura_anterior' => 0,
            'lectura_actual' => 27.5,
        ])
        ->assertHasNoTableActionErrors();

    $lectura = Lectura::where('contador_id', $contador->id)->first();

    expect((float) $lectura->consumo_m3)->toBe(27.5)
        ->and($lectura->usuario_id)->toBe(auth()->id())
        ->and($lectura->periodo_id)->toBe($this->periodo->id)
        ->and($lectura->fecha_lectura->toDateString())->toBe(now()->toDateString());
});

it('precarga la lectura anterior del contador en el modal', function () {
    // La lectura previa cae en el período que arma su propia factory, así que
    // el contador sigue pendiente en el período de la ruta.
    $anterior = Lectura::factory()->create(['lectura_actual' => 33.25]);

    Livewire::test(RutaLectura::class)
        ->mountTableAction('registrar', $anterior->contador)
        ->assertTableActionDataSet(['lectura_anterior' => 33.25]);
});

it('rechaza desde el modal una lectura menor que la anterior', function () {
    $anterior = Lectura::factory()->create(['lectura_actual' => 40]);

    Livewire::test(RutaLectura::class)
        ->callTableAction('registrar', $anterior->contador, data: [
            'lectura_anterior' => 40,
            'lectura_actual' => 12,
        ])
        ->assertHasTableActionErrors(['lectura_actual']);
});

it('saca de pendientes al contador recien leido y lo pasa a leidos', function () {
    $contador = Contador::factory()->create();

    $pagina = Livewire::test(RutaLectura::class)
        ->callTableAction('registrar', $contador, data: [
            'lectura_anterior' => 0,
            'lectura_actual' => 15,
        ]);

    $pagina->assertCanNotSeeTableRecords([$contador]);

    $pagina->call('cambiarVista', 'leidos')
        ->assertCanSeeTableRecords([$contador]);
});

it('no ofrece registrar cuando hoy queda fuera del periodo', function () {
    $viejo = Periodo::factory()->create([
        'anio' => 2026,
        'mes' => 1,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-01-31',
    ]);
    $contador = Contador::factory()->create();

    $pagina = Livewire::test(RutaLectura::class)->set('periodoId', $viejo->id);

    expect($pagina->instance()->puedeRegistrar())->toBeFalse()
        ->and($pagina->instance()->aviso)->toContain('está fuera del período 2026-01');

    $pagina->assertTableActionHidden('registrar', $contador);
});

it('esconde la ruta del menu si no hay ningun periodo abierto', function () {
    expect(RutaLectura::shouldRegisterNavigation())->toBeTrue();

    Periodo::query()->update(['cerrado_en' => now()]);

    expect(RutaLectura::shouldRegisterNavigation())->toBeFalse();
});

it('avisa en la pantalla cuando no hay periodo abierto', function () {
    Periodo::query()->update(['cerrado_en' => now()]);

    $pagina = Livewire::test(RutaLectura::class);

    expect($pagina->instance()->aviso)->toContain('No hay ningún período abierto');

    $pagina->assertSuccessful();
});
