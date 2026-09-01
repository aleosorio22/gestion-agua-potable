<?php

use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Factura;
use App\Models\Lectura;
use App\Models\User;
use Illuminate\Database\QueryException;

it('calcula consumo_m3 como columna generada por la base de datos', function () {
    $lectura = Lectura::factory()->create([
        'lectura_anterior' => 100.00,
        'lectura_actual' => 137.50,
    ]);

    expect((float) $lectura->fresh()->consumo_m3)->toBe(37.50);
});

it('no permite dos lecturas del mismo contador en el mismo periodo', function () {
    $contador = Contador::factory()->create();

    Lectura::factory()->create([
        'contador_id' => $contador->id,
        'periodo' => '2026-08',
    ]);

    Lectura::factory()->create([
        'contador_id' => $contador->id,
        'periodo' => '2026-08',
    ]);
})->throws(QueryException::class);

it('no permite facturar dos veces la misma lectura', function () {
    $lectura = Lectura::factory()->create();

    Factura::factory()->create(['lectura_id' => $lectura->id]);
    Factura::factory()->create(['lectura_id' => $lectura->id]);
})->throws(QueryException::class);

it('no permite dos clientes con el mismo nit', function () {
    Cliente::factory()->create(['nit' => '1234567-8']);
    Cliente::factory()->create(['nit' => '1234567-8']);
})->throws(QueryException::class);

it('no permite dos logins para el mismo cliente', function () {
    $cliente = Cliente::factory()->create();

    User::factory()->create(['cliente_id' => $cliente->id]);
    User::factory()->create(['cliente_id' => $cliente->id]);
})->throws(QueryException::class);

it('permite varios usuarios internos sin cliente asociado', function () {
    User::factory()->count(3)->create(['cliente_id' => null]);

    expect(User::whereNull('cliente_id')->count())->toBe(3);
});
