<?php

namespace App\Observers;

use App\Models\SerieDocumento;
use RuntimeException;

/**
 * La serie es el libro de correlativos de la entidad.
 *
 * Dos cosas la vuelven inútil si se tocan a mano: cambiarle el formato después
 * de haber entregado documentos (dos folios distintos con el mismo número), y
 * mover el contador (números repetidos o huecos que nadie puede explicar en
 * una auditoría). Ambas se bloquean aquí; lo que sí se puede es desactivar la
 * serie y abrir una nueva.
 */
class SerieDocumentoObserver
{
    /**
     * Piezas que definen cómo se ve el folio. Se congelan en cuanto la serie
     * entrega su primer documento.
     *
     * @var list<string>
     */
    private const CAMPOS_DE_FORMATO = [
        'tipo_documento',
        'codigo',
        'prefijo',
        'separador',
        'incluye_anio',
        'longitud_numero',
        'reinicia_cada_anio',
    ];

    public function saving(SerieDocumento $serie): void
    {
        if (! $serie->activa) {
            return;
        }

        // `activaPara()` resuelve la serie con ->first(): dos activas del mismo
        // tipo harían que el documento saliera con una u otra según el orden
        // que devuelva el motor.
        $otraActiva = SerieDocumento::query()
            ->where('tipo_documento', $serie->tipo_documento)
            ->where('activa', true)
            ->when($serie->exists, fn ($query) => $query->whereKeyNot($serie->getKey()))
            ->exists();

        if ($otraActiva) {
            throw new RuntimeException(
                "Ya hay una serie activa para {$serie->tipo_documento}. "
                .'Desactive la actual antes de activar esta.'
            );
        }
    }

    public function updating(SerieDocumento $serie): void
    {
        $modificados = array_keys($serie->getDirty());

        if ($serie->haEmitido() && array_intersect($modificados, self::CAMPOS_DE_FORMATO)) {
            throw new RuntimeException(
                "La serie {$serie->codigo} ya entregó documentos, así que su formato no puede cambiar. "
                .'Desactívela y cree una serie nueva con el formato que necesita.'
            );
        }

        $correlativo = array_intersect($modificados, ['siguiente_numero', 'ejercicio']);

        if ($correlativo && ! $serie->reservandoCorrelativo) {
            throw new RuntimeException(
                "El correlativo de la serie {$serie->codigo} solo avanza al emitir un documento. "
                .'No puede fijarse a mano.'
            );
        }
    }

    public function deleting(SerieDocumento $serie): void
    {
        if ($serie->haEmitido()) {
            throw new RuntimeException(
                "La serie {$serie->codigo} ya entregó documentos y no se elimina. Desactívela."
            );
        }
    }
}
