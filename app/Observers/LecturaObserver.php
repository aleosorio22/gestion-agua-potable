<?php

namespace App\Observers;

use App\Models\Lectura;
use RuntimeException;

/**
 * Protege las invariantes de la lectura que el esquema no puede imponer.
 */
class LecturaObserver
{
    public function creating(Lectura $lectura): void
    {
        $this->verificarPeriodoAbierto($lectura);
        $this->verificarFechaDentroDelPeriodo($lectura);
        $this->verificarEncadenamiento($lectura);
    }

    public function updating(Lectura $lectura): void
    {
        // Una lectura ya facturada es el origen del snapshot de la boleta:
        // si cambia, la boleta y la lectura quedan contradiciéndose.
        if ($lectura->esta_facturada) {
            throw new RuntimeException(
                "La lectura #{$lectura->id} ya fue facturada y no puede modificarse. "
                .'Anule la boleta primero.'
            );
        }

        $this->verificarPeriodoAbierto($lectura);
    }

    public function deleting(Lectura $lectura): void
    {
        if ($lectura->esta_facturada) {
            throw new RuntimeException(
                "La lectura #{$lectura->id} ya fue facturada y no puede eliminarse."
            );
        }
    }

    private function verificarPeriodoAbierto(Lectura $lectura): void
    {
        $periodo = $lectura->periodo()->first();

        if ($periodo?->esta_cerrado) {
            throw new RuntimeException(
                "El período {$periodo->etiqueta} está cerrado y no admite cambios en lecturas."
            );
        }
    }

    /**
     * La visita tiene que caer dentro del ciclo que dice cubrir.
     *
     * Sin esto el rango de fechas del período es decorativo: una lectura de
     * marzo se podía guardar en el período de septiembre, y de ahí sale una
     * boleta calculada con la tarifa vigente en marzo dentro de un mes que
     * dice cobrar septiembre.
     */
    private function verificarFechaDentroDelPeriodo(Lectura $lectura): void
    {
        $periodo = $lectura->periodo()->first();

        if ($periodo === null || $lectura->fecha_lectura === null) {
            return;
        }

        $fecha = $lectura->fecha_lectura;

        if ($fecha->betweenIncluded($periodo->fecha_inicio, $periodo->fecha_fin)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'La fecha de la visita (%s) no cae dentro del período %s, que va del %s al %s.',
            $fecha->format('d/m/Y'),
            $periodo->etiqueta,
            $periodo->fecha_inicio->format('d/m/Y'),
            $periodo->fecha_fin->format('d/m/Y'),
        ));
    }

    /**
     * La lectura anterior debe coincidir con la lectura actual de la visita
     * previa a ese contador. Un error de digitación aquí produce un consumo
     * incorrecto que nadie detecta.
     */
    private function verificarEncadenamiento(Lectura $lectura): void
    {
        $anterior = Lectura::query()
            ->where('contador_id', $lectura->contador_id)
            ->orderByDesc('fecha_lectura')
            ->orderByDesc('id')
            ->first();

        $esperada = $anterior ? (float) $anterior->lectura_actual : 0.0;

        if (abs((float) $lectura->lectura_anterior - $esperada) > 0.001) {
            throw new RuntimeException(
                "La lectura anterior ({$lectura->lectura_anterior}) no coincide con la "
                ."última lectura registrada del contador ({$esperada})."
            );
        }
    }
}
