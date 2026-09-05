<?php

namespace App\Services;

use App\Models\Boleta;
use App\Models\Lectura;
use App\Models\SerieDocumento;
use App\Models\Tarifa;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Emite la boleta de cobro a partir de una lectura.
 *
 * Concentra las reglas que el esquema no puede imponer solo: que exista tarifa
 * vigente, que el período esté abierto, y que el correlativo se consuma en la
 * misma transacción que inserta el documento.
 */
class EmisorBoletas
{
    public function __construct(
        private readonly int $diasParaVencer = 30,
    ) {}

    public function emitir(Lectura $lectura): Boleta
    {
        // consumo_m3 lo calcula la base de datos al insertar, así que una
        // instancia recién creada todavía no lo tiene cargado.
        $lectura->refresh();
        $lectura->loadMissing('contador.paja', 'periodo');

        if ($lectura->periodo->esta_cerrado) {
            throw new RuntimeException(
                "El período {$lectura->periodo->etiqueta} está cerrado y no admite emisiones."
            );
        }

        if ($lectura->esta_facturada) {
            throw new RuntimeException(
                "La lectura #{$lectura->id} ya tiene boleta emitida."
            );
        }

        $paja = $lectura->contador->paja;

        $tarifa = Tarifa::vigenteEn($paja->id, $lectura->fecha_lectura);

        if (! $tarifa) {
            throw new RuntimeException(
                "No hay tarifa vigente para la paja '{$paja->nombre}' "
                ."al {$lectura->fecha_lectura->toDateString()}."
            );
        }

        $serie = SerieDocumento::activaPara('boleta');

        if (! $serie) {
            throw new RuntimeException('No hay una serie activa configurada para boletas.');
        }

        $importes = $tarifa->calcularMonto(
            (float) $lectura->consumo_m3,
            (float) $paja->equivalencia_m3,
        );

        return DB::transaction(function () use ($lectura, $tarifa, $serie, $importes) {
            $correlativo = $serie->reservarNumero();

            return Boleta::create([
                'serie_id' => $serie->id,
                'ejercicio' => $correlativo['ejercicio'],
                'numero' => $correlativo['numero'],
                'folio' => $correlativo['folio'],
                'cliente_id' => $lectura->contador->cliente_id,
                'lectura_id' => $lectura->id,
                'tarifa_id' => $tarifa->id,
                'periodo_id' => $lectura->periodo_id,
                'consumo_m3' => $lectura->consumo_m3,
                'monto_base' => $importes['monto_base'],
                'monto_excedente' => $importes['monto_excedente'],
                'monto' => $importes['monto'],
                'fecha_emision' => now()->toDateString(),
                'fecha_vencimiento' => now()->addDays($this->diasParaVencer)->toDateString(),
            ]);
        });
    }
}
