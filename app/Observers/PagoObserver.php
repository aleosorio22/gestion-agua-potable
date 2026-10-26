<?php

namespace App\Observers;

use App\Models\Pago;
use App\Models\PagoEnvio;
use RuntimeException;

/**
 * Los pagos son inmutables: solo se admite revertirlos.
 */
class PagoObserver
{
    /**
     * Todo pago cobrado nace pendiente de avisarle al banco y al ERP.
     *
     * Vive acá y no en el servicio de cobro porque un pago se puede registrar
     * desde la ventanilla, desde un seeder o desde una importación: engancharlo
     * al modelo es lo único que cubre todos los caminos, incluidos los que
     * todavía no existen.
     */
    public function created(Pago $pago): void
    {
        PagoEnvio::create([
            'pago_id' => $pago->id,
            'llave' => PagoEnvio::llaveDe($pago),
        ]);
    }

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
