<?php

namespace App\Models\Concerns;

/**
 * Comportamiento común de los catálogos.
 *
 * En una entidad pública una fila de catálogo que ya respalda documentos
 * emitidos no se borra: se desactiva. Las claves foráneas ya son
 * `restrictOnDelete`, así que el motor lo impide de todas formas; este trait
 * solo traduce ese candado a un mensaje entendible antes de chocar con él.
 */
trait EsCatalogo
{
    /**
     * Relaciones cuya existencia impide borrar la fila, con la etiqueta que se
     * le muestra al usuario en formato "singular|plural".
     *
     * @return array<string, string>
     */
    abstract public function relacionesQueImpidenBorrado(): array;

    /**
     * Descripción del uso que bloquea el borrado, o null si la fila está libre.
     *
     * Ej.: '1 contador, 2 tarifas'
     */
    public function motivoDeUso(): ?string
    {
        $usos = [];

        foreach ($this->relacionesQueImpidenBorrado() as $relacion => $etiqueta) {
            $total = $this->{$relacion}()->count();

            if ($total > 0) {
                [$singular, $plural] = array_pad(explode('|', $etiqueta, 2), 2, $etiqueta);

                $usos[] = $total.' '.($total === 1 ? $singular : $plural);
            }
        }

        return $usos === [] ? null : implode(', ', $usos);
    }

    public function estaEnUso(): bool
    {
        return $this->motivoDeUso() !== null;
    }
}
