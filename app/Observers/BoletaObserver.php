<?php

namespace App\Observers;

use App\Models\Boleta;
use RuntimeException;

/**
 * La boleta es un documento contable: una vez emitida solo admite anularse
 * o marcarse como impresa.
 */
class BoletaObserver
{
    public function updating(Boleta $boleta): void
    {
        $camposPermitidos = [
            'impresa_en',
            'anulada_en',
            'anulada_por',
            'motivo_anulacion',
            'updated_at',
        ];

        $modificados = array_keys($boleta->getDirty());

        if (array_diff($modificados, $camposPermitidos)) {
            throw new RuntimeException(
                "La boleta {$boleta->folio} ya fue emitida y sus importes no pueden cambiar. "
                .'Anúlela y emita una nueva.'
            );
        }
    }

    public function deleting(Boleta $boleta): void
    {
        throw new RuntimeException(
            "La boleta {$boleta->folio} no se elimina. Para dejarla sin efecto, anúlela con motivo."
        );
    }
}
