<?php

use App\Models\Boleta;
use App\Models\Configuracion;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\MetodoPago;
use App\Models\Paja;
use App\Models\Periodo;
use App\Models\SerieDocumento;
use App\Models\Tarifa;
use App\Models\User;
use App\Services\EmisorBoletas;
use App\Services\RegistradorPagos;

/**
 * Prepara el escenario mínimo: una paja con tarifa vigente, un contador y el
 * período abierto.
 *
 * @return array{lectura: Lectura, periodo: Periodo}
 */
function escenarioDeLectura(float $consumo = 40.0): array
{
    SerieDocumento::factory()->create(['tipo_documento' => 'boleta', 'activa' => true]);
    SerieDocumento::factory()->paraRecibos()->create(['activa' => true]);

    $paja = Paja::factory()->create(['equivalencia_m3' => 30.00]);

    Tarifa::factory()->create([
        'paja_id' => $paja->id,
        'monto_base' => 50.00,
        'precio_m3_excedente' => 1.5000,
        'vigente_desde' => now()->startOfYear()->toDateString(),
    ]);

    $contador = Contador::factory()->create(['paja_id' => $paja->id]);
    $periodo = Periodo::factory()->create();

    $lectura = Lectura::factory()->create([
        'contador_id' => $contador->id,
        'periodo_id' => $periodo->id,
        'lectura_anterior' => 0,
        'lectura_actual' => $consumo,
    ]);

    return ['lectura' => $lectura, 'periodo' => $periodo];
}

it('emite la boleta con el monto calculado y folio correlativo', function () {
    ['lectura' => $lectura] = escenarioDeLectura(consumo: 40.0);

    $boleta = app(EmisorBoletas::class)->emitir($lectura);

    expect((float) $boleta->consumo_m3)->toBe(40.0)
        ->and((float) $boleta->monto_base)->toBe(50.0)
        ->and((float) $boleta->monto_excedente)->toBe(15.0)  // 10 m3 sobre 30, a Q1.50
        ->and((float) $boleta->monto)->toBe(65.0)
        ->and($boleta->numero)->toBe(1)
        ->and($boleta->folio)->toStartWith('BOL');
});

it('no emite dos boletas para la misma lectura', function () {
    ['lectura' => $lectura] = escenarioDeLectura();

    $emisor = app(EmisorBoletas::class);
    $emisor->emitir($lectura);
    $emisor->emitir($lectura->fresh());
})->throws(RuntimeException::class, 'ya tiene boleta emitida');

it('no emite si no hay tarifa vigente a la fecha de la lectura', function () {
    ['lectura' => $lectura] = escenarioDeLectura();

    Tarifa::query()->delete();

    app(EmisorBoletas::class)->emitir($lectura->fresh());
})->throws(RuntimeException::class, 'No hay tarifa vigente');

it('no emite en un periodo cerrado', function () {
    ['lectura' => $lectura, 'periodo' => $periodo] = escenarioDeLectura();

    $periodo->cerrar(User::factory()->create());

    app(EmisorBoletas::class)->emitir($lectura->fresh());
})->throws(RuntimeException::class, 'está cerrado');

it('deriva el estado de la boleta a partir de los pagos', function () {
    ['lectura' => $lectura] = escenarioDeLectura();

    $boleta = app(EmisorBoletas::class)->emitir($lectura);
    $metodo = MetodoPago::factory()->create();
    $cajera = User::factory()->create();

    expect($boleta->estado)->toBe('pendiente')
        ->and($boleta->saldo)->toBe(65.0);

    // Pago parcial: sigue pendiente.
    app(RegistradorPagos::class)->registrar($boleta, $metodo, $cajera, 30.00);

    expect($boleta->fresh()->estado)->toBe('pendiente')
        ->and($boleta->fresh()->saldo)->toBe(35.0);

    // Se completa: pasa a pagada sin que nadie actualice una columna.
    app(RegistradorPagos::class)->registrar($boleta->fresh(), $metodo, $cajera, 35.00);

    expect($boleta->fresh()->estado)->toBe('pagada')
        ->and($boleta->fresh()->saldo)->toBe(0.0);
});

it('marca vencida la boleta impaga cuya fecha ya paso', function () {
    $boleta = Boleta::factory()->vencida()->create();

    expect($boleta->estado)->toBe('vencida')
        ->and(Boleta::vencidas()->count())->toBe(1);
});

it('no cuenta los pagos revertidos en el saldo', function () {
    ['lectura' => $lectura] = escenarioDeLectura();

    $boleta = app(EmisorBoletas::class)->emitir($lectura);
    $cajera = User::factory()->create();

    $pago = app(RegistradorPagos::class)->registrar(
        $boleta,
        MetodoPago::factory()->create(),
        $cajera,
        65.00,
    );

    expect($boleta->fresh()->estado)->toBe('pagada');

    // Cheque rechazado: se revierte, y la boleta vuelve a deber.
    $pago->revertir($cajera, 'Cheque rechazado por el banco');

    expect($boleta->fresh()->estado)->toBe('pendiente')
        ->and($boleta->fresh()->saldo)->toBe(65.0)
        // El pago original sigue existiendo: no se borró nada.
        ->and($boleta->pagos()->count())->toBe(1);
});

it('no permite cobrar mas que el saldo pendiente', function () {
    ['lectura' => $lectura] = escenarioDeLectura();

    $boleta = app(EmisorBoletas::class)->emitir($lectura);

    app(RegistradorPagos::class)->registrar(
        $boleta,
        MetodoPago::factory()->create(),
        User::factory()->create(),
        1000.00,
    );
})->throws(RuntimeException::class, 'excede el saldo');

it('exige referencia cuando el metodo de pago lo requiere', function () {
    ['lectura' => $lectura] = escenarioDeLectura();

    $boleta = app(EmisorBoletas::class)->emitir($lectura);

    app(RegistradorPagos::class)->registrar(
        $boleta,
        MetodoPago::factory()->create(['requiere_referencia' => true]),
        User::factory()->create(),
        50.00,
    );
})->throws(RuntimeException::class, 'requiere número de referencia');

it('no cobra sobre una boleta anulada', function () {
    ['lectura' => $lectura] = escenarioDeLectura();

    $boleta = app(EmisorBoletas::class)->emitir($lectura);
    $boleta->anular(User::factory()->create(), 'Emitida por error');

    app(RegistradorPagos::class)->registrar(
        $boleta->fresh(),
        MetodoPago::factory()->create(),
        User::factory()->create(),
        50.00,
    );
})->throws(RuntimeException::class, 'está anulada');

it('toma el plazo de vencimiento de la configuracion de la oficina', function () {
    // El municipio de referencia vence al día siguiente de generar; otras
    // oficinas dan un mes. El plazo no puede estar clavado en el código.
    Configuracion::guardar('facturacion.dias_vencimiento', '1');

    ['lectura' => $lectura] = escenarioDeLectura();

    $boleta = app(EmisorBoletas::class)->emitir($lectura);

    expect($boleta->fecha_vencimiento->toDateString())
        ->toBe(now()->addDay()->toDateString());
});

it('cae en treinta dias si la configuracion no trae un plazo usable', function () {
    Configuracion::guardar('facturacion.dias_vencimiento', '0');

    ['lectura' => $lectura] = escenarioDeLectura();

    $boleta = app(EmisorBoletas::class)->emitir($lectura);

    expect($boleta->fecha_vencimiento->toDateString())
        ->toBe(now()->addDays(30)->toDateString());
});

it('cobra el excedente con las equivalencias reales de la oficina', function () {
    // Los recibos del municipio: media paja incluye 30 m³ con canon de Q80, y
    // el excedente va a Q4 por m³. Un consumo de 21 m³ en un cuarto de paja
    // (15 m³ incluidos) da 6 m³ de exceso, o sea Q24.
    SerieDocumento::factory()->create(['tipo_documento' => 'boleta', 'activa' => true]);

    $cuartoDePaja = Paja::factory()->create(['nombre' => '1/4 paja', 'equivalencia_m3' => 15.00]);

    Tarifa::factory()->create([
        'paja_id' => $cuartoDePaja->id,
        'monto_base' => 40.00,
        'precio_m3_excedente' => 4.0000,
        'vigente_desde' => now()->startOfYear()->toDateString(),
    ]);

    $contador = Contador::factory()->create(['paja_id' => $cuartoDePaja->id]);

    // El marcador viene de la visita del mes pasado: el observer exige que la
    // lectura anterior coincida con la última registrada del contador.
    Lectura::factory()->create([
        'contador_id' => $contador->id,
        'periodo_id' => Periodo::factory()->create()->id,
        'lectura_anterior' => 0,
        'lectura_actual' => 718,
    ]);

    $lectura = Lectura::factory()->create([
        'contador_id' => $contador->id,
        'periodo_id' => Periodo::factory()->create()->id,
        'lectura_anterior' => 718,
        'lectura_actual' => 739,
    ]);

    $boleta = app(EmisorBoletas::class)->emitir($lectura);

    expect((float) $boleta->consumo_m3)->toBe(21.0)
        ->and((float) $boleta->monto_base)->toBe(40.0)
        ->and((float) $boleta->monto_excedente)->toBe(24.0)
        ->and((float) $boleta->monto)->toBe(64.0);
});

it('no cobra excedente cuando el consumo queda justo en el limite', function () {
    // La otra tarjeta del mismo recibo: 30 m³ consumidos con 30 m³ incluidos,
    // y en el documento no aparece línea de exceso para ese mes.
    SerieDocumento::factory()->create(['tipo_documento' => 'boleta', 'activa' => true]);

    $mediaPaja = Paja::factory()->create(['nombre' => '1/2 paja', 'equivalencia_m3' => 30.00]);

    Tarifa::factory()->create([
        'paja_id' => $mediaPaja->id,
        'monto_base' => 80.00,
        'precio_m3_excedente' => 4.0000,
        'vigente_desde' => now()->startOfYear()->toDateString(),
    ]);

    $contador = Contador::factory()->create(['paja_id' => $mediaPaja->id]);

    // El marcador viene de la visita del mes pasado: el observer exige que la
    // lectura anterior coincida con la última registrada del contador.
    Lectura::factory()->create([
        'contador_id' => $contador->id,
        'periodo_id' => Periodo::factory()->create()->id,
        'lectura_anterior' => 0,
        'lectura_actual' => 1991,
    ]);

    $lectura = Lectura::factory()->create([
        'contador_id' => $contador->id,
        'periodo_id' => Periodo::factory()->create()->id,
        'lectura_anterior' => 1991,
        'lectura_actual' => 2021,
    ]);

    $boleta = app(EmisorBoletas::class)->emitir($lectura);

    expect((float) $boleta->consumo_m3)->toBe(30.0)
        ->and((float) $boleta->monto_excedente)->toBe(0.0)
        ->and((float) $boleta->monto)->toBe(80.0);
});
