<?php

use App\Models\Paja;
use App\Models\Tarifa;
use Illuminate\Database\QueryException;

it('aplica la tarifa mas reciente que ya habia empezado', function () {
    $paja = Paja::factory()->create();

    $vieja = Tarifa::factory()->create([
        'paja_id' => $paja->id,
        'monto_base' => 50.00,
        'vigente_desde' => '2025-01-01',
    ]);

    $nueva = Tarifa::factory()->create([
        'paja_id' => $paja->id,
        'monto_base' => 60.00,
        'vigente_desde' => '2026-01-01',
    ]);

    // El caso que antes fallaba en silencio: con dos tarifas registradas y sin
    // haber "cerrado" la vieja, se cobraba la de Q50.
    expect(Tarifa::vigenteEn($paja->id, '2026-03-15')->id)->toBe($nueva->id)
        ->and(Tarifa::vigenteEn($paja->id, '2025-03-15')->id)->toBe($vieja->id)
        ->and(Tarifa::vigenteEn($paja->id, '2026-01-01')->id)->toBe($nueva->id);
});

it('no devuelve tarifa para fechas anteriores a la primera registrada', function () {
    $paja = Paja::factory()->create();

    Tarifa::factory()->create([
        'paja_id' => $paja->id,
        'vigente_desde' => '2026-01-01',
    ]);

    expect(Tarifa::vigenteEn($paja->id, '2025-12-31'))->toBeNull();
});

it('impide dos tarifas que empiecen el mismo dia para la misma paja', function () {
    $paja = Paja::factory()->create();

    Tarifa::factory()->create(['paja_id' => $paja->id, 'vigente_desde' => '2026-01-01']);
    Tarifa::factory()->create(['paja_id' => $paja->id, 'vigente_desde' => '2026-01-01']);
})->throws(QueryException::class);

it('deriva el fin de vigencia de la tarifa siguiente', function () {
    $paja = Paja::factory()->create();

    $vieja = Tarifa::factory()->create([
        'paja_id' => $paja->id,
        'vigente_desde' => '2025-01-01',
    ]);

    $nueva = Tarifa::factory()->create([
        'paja_id' => $paja->id,
        'vigente_desde' => '2026-01-01',
    ]);

    expect($vieja->vigente_hasta->toDateString())->toBe('2025-12-31')
        ->and($vieja->es_vigente)->toBeFalse()
        ->and($nueva->vigente_hasta)->toBeNull()
        ->and($nueva->es_vigente)->toBeTrue();
});

it('calcula el monto con cuota base y excedente', function () {
    $tarifa = Tarifa::factory()->create([
        'monto_base' => 50.00,
        'precio_m3_excedente' => 1.5000,
    ]);

    // Consumo dentro de lo contratado: solo cuota base.
    expect($tarifa->calcularMonto(consumoM3: 20, equivalenciaM3: 30))
        ->toMatchArray(['monto_base' => 50.00, 'monto_excedente' => 0.0, 'monto' => 50.00]);

    // 10 m3 de excedente a Q1.50.
    expect($tarifa->calcularMonto(consumoM3: 40, equivalenciaM3: 30))
        ->toMatchArray(['monto_base' => 50.00, 'monto_excedente' => 15.00, 'monto' => 65.00]);
});
