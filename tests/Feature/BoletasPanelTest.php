<?php

use App\Filament\Admin\Resources\Boletas\BoletaResource;
use App\Filament\Admin\Resources\Lecturas\LecturaResource;
use App\Models\Boleta;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Paja;
use App\Models\Periodo;
use App\Models\SerieDocumento;
use App\Models\Tarifa;
use App\Models\User;
use App\Services\EmisorBoletas;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Emisión, anulación e impresión desde el panel. Lo que interesa demostrar es
 * que las reglas duras de `EmisorBoletas` lleguen como notificación y no como
 * pantalla de error, y que el recibo acumule la deuda del servicio.
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

/**
 * Un servicio con tarifa vigente y serie activa: lo mínimo para poder emitir.
 */
function servicioFacturable(): Contador
{
    SerieDocumento::factory()->create(['tipo_documento' => 'boleta', 'activa' => true]);

    $paja = Paja::factory()->create(['equivalencia_m3' => 15.00]);

    Tarifa::factory()->create([
        'paja_id' => $paja->id,
        'monto_base' => 40.00,
        'precio_m3_excedente' => 4.0000,
        'vigente_desde' => now()->subYear()->toDateString(),
    ]);

    return Contador::factory()->create(['paja_id' => $paja->id]);
}

function lecturaDe(Contador $contador, float $consumo, ?Periodo $periodo = null): Lectura
{
    $anterior = $contador->ultimaLectura();

    return Lectura::factory()->create([
        'contador_id' => $contador->id,
        'periodo_id' => ($periodo ?? Periodo::factory()->create())->id,
        'lectura_anterior' => (float) ($anterior?->lectura_actual ?? 0),
        'lectura_actual' => (float) ($anterior?->lectura_actual ?? 0) + $consumo,
    ]);
}

it('pinta el listado de boletas', function () {
    Boleta::factory()->count(3)->create();

    Livewire::test(BoletaResource::getPages()['index']->getPage())
        ->assertSuccessful();
});

it('no deja crear boletas a mano', function () {
    expect(BoletaResource::canCreate())->toBeFalse();
});

it('emite la boleta de una lectura desde el listado', function () {
    $lectura = lecturaDe(servicioFacturable(), consumo: 21);

    Livewire::test(LecturaResource::getPages()['index']->getPage())
        ->filterTable('periodo_id', null)
        ->callTableAction('emitir', $lectura);

    $boleta = Boleta::where('lectura_id', $lectura->id)->first();

    expect($boleta)->not->toBeNull()
        ->and((float) $boleta->monto_base)->toBe(40.0)
        ->and((float) $boleta->monto_excedente)->toBe(24.0)
        ->and((float) $boleta->monto)->toBe(64.0);
});

it('no ofrece emitir una lectura que ya tiene boleta', function () {
    $facturada = Boleta::factory()->create()->lectura;

    Livewire::test(LecturaResource::getPages()['index']->getPage())
        ->filterTable('periodo_id', null)
        ->assertTableActionHidden('emitir', $facturada);
});

it('avisa en vez de reventar cuando no hay tarifa vigente', function () {
    SerieDocumento::factory()->create(['tipo_documento' => 'boleta', 'activa' => true]);

    // Sin tarifa para su paja: EmisorBoletas lanza excepción y la acción tiene
    // que atraparla.
    $lectura = lecturaDe(Contador::factory()->create(), consumo: 10);

    Livewire::test(LecturaResource::getPages()['index']->getPage())
        ->filterTable('periodo_id', null)
        ->callTableAction('emitir', $lectura)
        ->assertSuccessful();

    expect(Boleta::where('lectura_id', $lectura->id)->exists())->toBeFalse();
});

it('emite en lote las lecturas seleccionadas y salta las ya facturadas', function () {
    $contador = servicioFacturable();
    $periodo = Periodo::factory()->create();

    $primera = lecturaDe($contador, consumo: 10, periodo: $periodo);
    $segunda = lecturaDe(Contador::factory()->create(['paja_id' => $contador->paja_id]), consumo: 18, periodo: $periodo);
    $yaFacturada = Boleta::factory()->create()->lectura;

    Livewire::test(LecturaResource::getPages()['index']->getPage())
        ->filterTable('periodo_id', null)
        ->callTableBulkAction('emitirEnLote', [$primera, $segunda, $yaFacturada]);

    expect(Boleta::where('lectura_id', $primera->id)->exists())->toBeTrue()
        ->and(Boleta::where('lectura_id', $segunda->id)->exists())->toBeTrue()
        ->and(Boleta::where('lectura_id', $yaFacturada->id)->count())->toBe(1);
});

it('anula una boleta dejando autor y motivo', function () {
    $boleta = Boleta::factory()->create();

    Livewire::test(BoletaResource::getPages()['index']->getPage())
        ->callTableAction('anular', $boleta, data: ['motivo' => 'Lectura mal tomada']);

    $boleta->refresh();

    expect($boleta->esta_anulada)->toBeTrue()
        ->and($boleta->motivo_anulacion)->toBe('Lectura mal tomada')
        ->and($boleta->anulada_por)->toBe(auth()->id());
});

it('exige el motivo para anular', function () {
    $boleta = Boleta::factory()->create();

    Livewire::test(BoletaResource::getPages()['index']->getPage())
        ->callTableAction('anular', $boleta, data: ['motivo' => ''])
        ->assertHasTableActionErrors(['motivo']);

    expect($boleta->fresh()->esta_anulada)->toBeFalse();
});

it('no ofrece anular una boleta ya anulada', function () {
    $boleta = Boleta::factory()->create();
    $boleta->anular(auth()->user(), 'Duplicada');

    Livewire::test(BoletaResource::getPages()['index']->getPage())
        ->assertTableActionHidden('anular', $boleta);
});

it('no permite borrar una boleta ni con permiso de shield', function () {
    $boleta = Boleta::factory()->create();

    expect(auth()->user()->can('delete', $boleta))->toBeFalse()
        ->and(auth()->user()->can('forceDelete', $boleta))->toBeFalse();
});

it('arma el recibo con toda la deuda pendiente del servicio', function () {
    $contador = servicioFacturable();

    $meses = collect(range(1, 3))->map(function () use ($contador): Boleta {
        $lectura = lecturaDe($contador, consumo: 21);

        return app(EmisorBoletas::class)->emitir($lectura);
    });

    $respuesta = $this->get(route('recibos.contador', $contador));

    $respuesta->assertOk()
        ->assertSee($contador->codigo)
        ->assertSee($contador->cliente->nombre)
        // Tres meses de canon Q40 más su exceso de Q24: Q192 acumulados.
        ->assertSee('TOTAL Q.192.00')
        ->assertSee('Incluye 3 meses pendientes.')
        ->assertSee('Canon de Agua')
        ->assertSee('Exceso de Agua / Servicio de Agua por Consumo');

    foreach ($meses as $boleta) {
        $respuesta->assertSee($boleta->folio);
    }
});

it('deja constancia de la impresion en las boletas del recibo', function () {
    $contador = servicioFacturable();
    $boleta = app(EmisorBoletas::class)->emitir(lecturaDe($contador, consumo: 21));

    expect($boleta->impresa_en)->toBeNull();

    $this->get(route('recibos.contador', $contador))->assertOk();

    expect($boleta->fresh()->impresa_en)->not->toBeNull();
});

it('no mete en el recibo las boletas de otro servicio', function () {
    $contador = servicioFacturable();
    $otro = Contador::factory()->create(['paja_id' => $contador->paja_id]);

    app(EmisorBoletas::class)->emitir(lecturaDe($contador, consumo: 21));
    $ajena = app(EmisorBoletas::class)->emitir(lecturaDe($otro, consumo: 21));

    $this->get(route('recibos.contador', $contador))
        ->assertOk()
        ->assertDontSee($ajena->folio);
});

it('dice que no hay saldo cuando el servicio esta al dia', function () {
    $contador = servicioFacturable();

    $this->get(route('recibos.contador', $contador))
        ->assertOk()
        ->assertSee('no tiene saldo pendiente', escape: false);
});

it('deja el recibo fuera del alcance de quien no puede ver boletas', function () {
    $contador = servicioFacturable();

    $this->actingAs(User::factory()->create());

    $this->get(route('recibos.contador', $contador))->assertForbidden();
});
