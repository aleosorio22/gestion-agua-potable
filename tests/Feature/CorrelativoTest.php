<?php

use App\Models\SerieDocumento;
use Illuminate\Support\Facades\DB;

it('arma el folio segun la configuracion de la serie', function (array $config, int $numero, string $esperado) {
    $serie = SerieDocumento::factory()->make($config);

    expect($serie->formatearFolio($numero, 2026))->toBe($esperado);
})->with([
    'sin año, sin separador' => [
        ['prefijo' => 'BOL', 'separador' => '', 'incluye_anio' => false, 'longitud_numero' => 6],
        9130,
        'BOL009130',
    ],
    'con año y separador' => [
        ['prefijo' => 'BOL', 'separador' => '-', 'incluye_anio' => true, 'longitud_numero' => 6],
        3131,
        'BOL-2026003131',
    ],
    'recibo de 5 dígitos' => [
        ['prefijo' => 'REC', 'separador' => '-', 'incluye_anio' => false, 'longitud_numero' => 5],
        412,
        'REC-00412',
    ],
]);

it('entrega numeros consecutivos sin repetir', function () {
    $serie = SerieDocumento::factory()->create(['siguiente_numero' => 1]);

    $numeros = collect(range(1, 5))->map(
        fn () => DB::transaction(fn () => $serie->reservarNumero()['numero'])
    );

    expect($numeros->all())->toBe([1, 2, 3, 4, 5])
        ->and($serie->fresh()->siguiente_numero)->toBe(6);
});

it('no consume el numero si la transaccion falla', function () {
    $serie = SerieDocumento::factory()->create(['siguiente_numero' => 1]);

    try {
        DB::transaction(function () use ($serie) {
            $serie->reservarNumero();

            throw new RuntimeException('algo salió mal después de reservar');
        });
    } catch (RuntimeException) {
        // Se ignora a propósito: lo que se comprueba es el rollback.
    }

    // El número sigue libre: no quedó hueco en la numeración.
    expect($serie->fresh()->siguiente_numero)->toBe(1);
});

// El guard que exige transacción no se puede probar aquí: RefreshDatabase
// envuelve cada prueba en una transacción, así que DB::transactionLevel()
// nunca es cero. El guard sigue protegiendo en ejecución real.

it('reinicia la numeracion al cambiar de ejercicio', function () {
    $serie = SerieDocumento::factory()->create([
        'reinicia_cada_anio' => true,
        'ejercicio' => (int) now()->year - 1,
        'siguiente_numero' => 458,
    ]);

    $correlativo = DB::transaction(fn () => $serie->reservarNumero());

    expect($correlativo['numero'])->toBe(1)
        ->and($correlativo['ejercicio'])->toBe((int) now()->year);
});
