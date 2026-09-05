<?php

namespace App\Services;

use App\Models\Boleta;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\SerieDocumento;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Registra el cobro en ventanilla y emite el recibo numerado.
 */
class RegistradorPagos
{
    public function registrar(
        Boleta $boleta,
        MetodoPago $metodoPago,
        User $usuario,
        float $monto,
        ?string $referencia = null,
        ?string $fechaPago = null,
    ): Pago {
        if ($boleta->esta_anulada) {
            throw new RuntimeException("La boleta {$boleta->folio} está anulada.");
        }

        if ($monto <= 0) {
            throw new RuntimeException('El monto del pago debe ser mayor que cero.');
        }

        if ($monto > $boleta->saldo) {
            throw new RuntimeException(
                "El monto excede el saldo pendiente de la boleta ({$boleta->saldo})."
            );
        }

        if ($metodoPago->requiere_referencia && blank($referencia)) {
            throw new RuntimeException(
                "El método de pago '{$metodoPago->nombre}' requiere número de referencia."
            );
        }

        $serie = SerieDocumento::activaPara('recibo_pago');

        if (! $serie) {
            throw new RuntimeException('No hay una serie activa configurada para recibos de pago.');
        }

        return DB::transaction(function () use ($boleta, $metodoPago, $usuario, $monto, $referencia, $fechaPago, $serie) {
            $correlativo = $serie->reservarNumero();

            return Pago::create([
                'serie_id' => $serie->id,
                'ejercicio' => $correlativo['ejercicio'],
                'numero' => $correlativo['numero'],
                'folio' => $correlativo['folio'],
                'boleta_id' => $boleta->id,
                'metodo_pago_id' => $metodoPago->id,
                'usuario_id' => $usuario->id,
                'monto' => $monto,
                'fecha_pago' => $fechaPago ?? now()->toDateString(),
                'referencia' => $referencia,
            ]);
        });
    }
}
