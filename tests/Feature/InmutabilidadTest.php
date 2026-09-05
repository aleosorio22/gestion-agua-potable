<?php

use App\Models\Boleta;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\User;

it('no permite modificar una lectura ya facturada', function () {
    $boleta = Boleta::factory()->create();

    $boleta->lectura->update(['lectura_actual' => 999]);
})->throws(RuntimeException::class, 'ya fue facturada');

it('no permite eliminar una lectura ya facturada', function () {
    $boleta = Boleta::factory()->create();

    $boleta->lectura->delete();
})->throws(RuntimeException::class, 'ya fue facturada');

it('no permite registrar lecturas en un periodo cerrado', function () {
    $periodo = Periodo::factory()->cerrado()->create();

    Lectura::factory()->create(['periodo_id' => $periodo->id]);
})->throws(RuntimeException::class, 'está cerrado');

it('exige que la lectura anterior coincida con la ultima del contador', function () {
    $contador = Contador::factory()->create();

    Lectura::factory()->create([
        'contador_id' => $contador->id,
        'lectura_anterior' => 0,
        'lectura_actual' => 100,
    ]);

    // La siguiente visita debería arrancar en 100, no en 80.
    Lectura::factory()->create([
        'contador_id' => $contador->id,
        'periodo_id' => Periodo::factory()->create()->id,
        'lectura_anterior' => 80,
        'lectura_actual' => 150,
    ]);
})->throws(RuntimeException::class, 'no coincide');

it('acepta la lectura encadenada correctamente', function () {
    $contador = Contador::factory()->create();

    $primera = Lectura::factory()->create([
        'contador_id' => $contador->id,
        'lectura_anterior' => 0,
        'lectura_actual' => 100,
    ]);

    $segunda = Lectura::factory()
        ->siguienteDe($primera)
        ->create(['periodo_id' => Periodo::factory()->create()->id])
        ->fresh(); // consumo_m3 lo calcula la BD al insertar

    expect((float) $segunda->lectura_anterior)->toBe(100.0)
        ->and((float) $segunda->consumo_m3)->toBe(
            round((float) $segunda->lectura_actual - 100.0, 2)
        );
});

it('no permite editar los importes de una boleta emitida', function () {
    $boleta = Boleta::factory()->create();

    $boleta->update(['monto' => 1.00]);
})->throws(RuntimeException::class, 'no pueden cambiar');

it('permite anular una boleta y marcarla impresa', function () {
    $boleta = Boleta::factory()->create();
    $usuario = User::factory()->create();

    $boleta->update(['impresa_en' => now()]);
    $boleta->anular($usuario, 'Emitida por error');

    expect($boleta->fresh()->estado)->toBe('anulada')
        ->and($boleta->fresh()->anulada_por)->toBe($usuario->id)
        ->and($boleta->fresh()->motivo_anulacion)->toBe('Emitida por error');
});

it('no permite eliminar una boleta', function () {
    Boleta::factory()->create()->delete();
})->throws(RuntimeException::class, 'no se elimina');

it('no permite editar un pago', function () {
    Pago::factory()->create()->update(['monto' => 1.00]);
})->throws(RuntimeException::class, 'no se edita');

it('no permite eliminar un pago', function () {
    Pago::factory()->create()->delete();
})->throws(RuntimeException::class, 'no se elimina');

it('permite revertir un pago dejando rastro', function () {
    $pago = Pago::factory()->create();
    $usuario = User::factory()->create();

    $pago->revertir($usuario, 'Cheque rechazado');

    expect($pago->fresh()->esta_revertido)->toBeTrue()
        ->and($pago->fresh()->revertido_por)->toBe($usuario->id)
        ->and(Pago::vigentes()->count())->toBe(0)
        ->and(Pago::revertidos()->count())->toBe(1);
});
