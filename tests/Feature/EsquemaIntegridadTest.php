<?php

use App\Models\Boleta;
use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Documento;
use App\Models\Lectura;
use App\Models\Periodo;
use App\Models\Predio;
use App\Models\TipoDocumento;
use Illuminate\Database\QueryException;

it('calcula consumo_m3 como columna generada por la base de datos', function () {
    $lectura = Lectura::factory()->create([
        'lectura_anterior' => 0,
        'lectura_actual' => 137.50,
    ]);

    expect((float) $lectura->fresh()->consumo_m3)->toBe(137.50);
});

it('no permite dos lecturas del mismo contador en el mismo periodo', function () {
    $contador = Contador::factory()->create();
    $periodo = Periodo::factory()->create();

    Lectura::factory()->create([
        'contador_id' => $contador->id,
        'periodo_id' => $periodo->id,
        'lectura_anterior' => 0,
        'lectura_actual' => 50,
    ]);

    Lectura::factory()->create([
        'contador_id' => $contador->id,
        'periodo_id' => $periodo->id,
        'lectura_anterior' => 50,
        'lectura_actual' => 90,
    ]);
})->throws(QueryException::class);

it('no permite facturar dos veces la misma lectura', function () {
    $lectura = Lectura::factory()->create();

    Boleta::factory()->create(['lectura_id' => $lectura->id]);
    Boleta::factory()->create(['lectura_id' => $lectura->id]);
})->throws(QueryException::class);

it('no permite dos clientes con el mismo nit', function () {
    Cliente::factory()->create(['nit' => '1234567-8']);
    Cliente::factory()->create(['nit' => '1234567-8']);
})->throws(QueryException::class);

it('no permite dos boletas con el mismo folio', function () {
    Boleta::factory()->create(['folio' => 'BOL-2026000001']);
    Boleta::factory()->create(['folio' => 'BOL-2026000001']);
})->throws(QueryException::class);

it('separa el predio del contador que tiene instalado', function () {
    $predio = Predio::factory()->create([
        'aldea' => 'El Porvenir',
        'zona' => '0',
        'numero_casa' => '1-31',
    ]);

    $contador = Contador::factory()->create(['predio_id' => $predio->id]);

    expect($contador->predio->direccion_completa)->toContain('El Porvenir')
        ->and($contador->predio->direccion_completa)->toContain('casa 1-31')
        // La zona '0' es un valor válido: no debe desaparecer por ser falsy.
        ->and($contador->predio->direccion_completa)->toContain('zona 0')
        ->and($predio->contadores)->toHaveCount(1);
});

it('liga los documentos de propiedad al predio y los de identidad al cliente', function () {
    $cliente = Cliente::factory()->create();
    $predio = Predio::factory()->create();

    $reciboLuz = Documento::factory()->create([
        'cliente_id' => $cliente->id,
        'predio_id' => $predio->id,
        'tipo_documento_id' => TipoDocumento::factory()->dePredio()->create()->id,
    ]);

    $dpi = Documento::factory()->create([
        'cliente_id' => $cliente->id,
        'predio_id' => null,
    ]);

    expect(Documento::dePredio($predio->id)->pluck('id')->all())->toBe([$reciboLuz->id])
        ->and(Documento::deLaPersona()->pluck('id')->all())->toBe([$dpi->id])
        ->and($cliente->documentos)->toHaveCount(2);
});

it('permite que un cliente tenga servicio en varios predios', function () {
    $cliente = Cliente::factory()->create();

    Contador::factory()->count(2)->create(['cliente_id' => $cliente->id]);

    expect($cliente->contadores)->toHaveCount(2)
        ->and($cliente->predios()->count())->toBe(2);
});
