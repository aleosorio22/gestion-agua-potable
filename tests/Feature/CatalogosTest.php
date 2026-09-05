<?php

use App\Models\Boleta;
use App\Models\Contador;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Paja;
use App\Models\Sector;
use App\Models\SerieDocumento;
use App\Models\Tarifa;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Tarifas: el precio con el que ya se cobró es parte del expediente
|--------------------------------------------------------------------------
*/

it('no permite modificar una tarifa que ya emitio boletas', function () {
    $boleta = Boleta::factory()->create();

    $boleta->tarifa->update(['monto_base' => 999.00]);
})->throws(RuntimeException::class, 'no puede modificarse');

it('no permite eliminar una tarifa que ya emitio boletas', function () {
    Boleta::factory()->create()->tarifa->delete();
})->throws(RuntimeException::class, 'no puede eliminarse');

it('permite corregir una tarifa que todavia no ha cobrado nada', function () {
    $tarifa = Tarifa::factory()->create(['monto_base' => 5.00]);

    $tarifa->update(['monto_base' => 50.00]);

    expect((float) $tarifa->fresh()->monto_base)->toBe(50.0);
});

/*
|--------------------------------------------------------------------------
| Series: el libro de correlativos no se reescribe
|--------------------------------------------------------------------------
*/

it('congela el formato de la serie en cuanto entrega un documento', function () {
    $boleta = Boleta::factory()->create();

    $boleta->serie->update(['prefijo' => 'OTRO']);
})->throws(RuntimeException::class, 'su formato no puede cambiar');

it('deja desactivar una serie que ya emitio, porque no es parte del formato', function () {
    $boleta = Boleta::factory()->create();

    $boleta->serie->update(['activa' => false]);

    expect($boleta->serie->fresh()->activa)->toBeFalse();
});

it('no deja fijar el correlativo a mano', function () {
    SerieDocumento::factory()->create()->update(['siguiente_numero' => 9130]);
})->throws(RuntimeException::class, 'solo avanza al emitir');

it('deja que reservarNumero avance el correlativo', function () {
    $serie = SerieDocumento::factory()->create(['siguiente_numero' => 9130]);

    $folio = DB::transaction(fn (): array => $serie->reservarNumero());

    expect($folio['numero'])->toBe(9130)
        ->and($serie->fresh()->siguiente_numero)->toBe(9131);
});

it('reinicia el ejercicio sin que el guard lo confunda con una edicion', function () {
    $serie = SerieDocumento::factory()->create([
        'reinicia_cada_anio' => true,
        'ejercicio' => (int) now()->year - 1,
        'siguiente_numero' => 458,
    ]);

    $folio = DB::transaction(fn (): array => $serie->reservarNumero());

    expect($folio['numero'])->toBe(1)
        ->and($folio['ejercicio'])->toBe((int) now()->year);
});

it('no admite dos series activas del mismo tipo de documento', function () {
    SerieDocumento::factory()->create(['tipo_documento' => 'boleta']);
    SerieDocumento::factory()->create(['tipo_documento' => 'boleta']);
})->throws(RuntimeException::class, 'Ya hay una serie activa');

it('admite una serie activa por cada tipo de documento', function () {
    SerieDocumento::factory()->create(['tipo_documento' => 'boleta']);
    SerieDocumento::factory()->paraRecibos()->create();

    expect(SerieDocumento::activaPara('boleta'))->not->toBeNull()
        ->and(SerieDocumento::activaPara('recibo_pago'))->not->toBeNull();
});

it('deja abrir una serie nueva si la anterior queda desactivada', function () {
    $vieja = SerieDocumento::factory()->create(['tipo_documento' => 'boleta']);
    $vieja->update(['activa' => false]);

    $nueva = SerieDocumento::factory()->create([
        'tipo_documento' => 'boleta',
        'prefijo' => 'BOL2',
    ]);

    expect(SerieDocumento::activaPara('boleta')->id)->toBe($nueva->id);
});

it('no elimina una serie que ya entrego documentos', function () {
    Boleta::factory()->create()->serie->delete();
})->throws(RuntimeException::class, 'no se elimina');

/*
|--------------------------------------------------------------------------
| EsCatalogo: qué está en uso y por qué
|--------------------------------------------------------------------------
*/

it('reporta el uso que impide borrar una fila de catalogo', function () {
    $paja = Paja::factory()->create();
    Contador::factory()->create(['paja_id' => $paja->id]);
    // Fechas distintas: UNIQUE(paja_id, vigente_desde) impide dos que
    // arranquen el mismo día.
    Tarifa::factory()->create(['paja_id' => $paja->id, 'vigente_desde' => '2026-01-01']);
    Tarifa::factory()->create(['paja_id' => $paja->id, 'vigente_desde' => '2026-07-01']);

    expect($paja->estaEnUso())->toBeTrue()
        ->and($paja->motivoDeUso())->toBe('1 contador, 2 tarifas');
});

it('deja libre una fila de catalogo que nadie referencia', function () {
    expect(Sector::factory()->create()->estaEnUso())->toBeFalse()
        ->and(MetodoPago::factory()->create()->motivoDeUso())->toBeNull();
});

it('impide en la base de datos borrar un catalogo en uso', function () {
    $metodo = MetodoPago::factory()->create();
    Pago::factory()->create(['metodo_pago_id' => $metodo->id]);

    $metodo->delete();
})->throws(QueryException::class);
