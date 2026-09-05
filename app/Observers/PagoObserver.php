<?php

namespace App\Observers;

use App\Models\Pago;
use RuntimeException;

/**
 * Los pagos son inmutables: solo se admite revertirlos.
 */
class PagoObserver
{
    public function updating(Pago $pago): void
    {
        $camposDeReverso = ['revertido_en', 'revertido_por', 'motivo_reverso'];

        $modificados = array_keys($pago->getDirty());

        if (array_diff($modificados, $camposDeReverso)) {
            throw new RuntimeException(
                'Un pago no se edita. Para corregirlo, reviértalo y registre uno nuevo.'
            );
        }
    }

    public function deleting(Pago $pago): void
    {
        throw new RuntimeException(
            'Un pago no se elimina. Para anularlo, reviértalo con motivo.'
        );
    }
}
