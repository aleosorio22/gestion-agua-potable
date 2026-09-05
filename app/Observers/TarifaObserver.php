<?php

namespace App\Observers;

use App\Models\Tarifa;
use RuntimeException;

/**
 * Una tarifa que ya sirvió para cobrar es parte del expediente: cambiarla
 * reescribiría el precio con el que se emitieron boletas que ya están en manos
 * del vecino. Para subir el precio se registra una tarifa nueva con otra fecha
 * de vigencia; la anterior queda como historia.
 *
 * Mientras no haya cobrado nada sí se puede corregir, que es el caso real de
 * un dedazo al capturarla.
 */
class TarifaObserver
{
    public function updating(Tarifa $tarifa): void
    {
        if ($tarifa->boletas()->exists()) {
            throw new RuntimeException(
                'Esta tarifa ya se usó para emitir boletas y no puede modificarse. '
                .'Registre una tarifa nueva con la fecha desde la que rige el precio nuevo.'
            );
        }
    }

    public function deleting(Tarifa $tarifa): void
    {
        if ($tarifa->boletas()->exists()) {
            throw new RuntimeException(
                'Esta tarifa ya se usó para emitir boletas y no puede eliminarse.'
            );
        }
    }
}
