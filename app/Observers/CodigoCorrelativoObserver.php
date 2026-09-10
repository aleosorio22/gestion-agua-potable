<?php

namespace App\Observers;

use App\Models\Correlativo;
use Illuminate\Database\Eloquent\Model;

/**
 * Pone el correlativo al día con el código que realmente se guardó.
 *
 * Vive en un observer y no en cada pantalla porque un cliente se puede dar de
 * alta desde el asistente, desde la ficha, desde un seeder o desde una
 * importación: enganchar el contador en el modelo es lo único que cubre todos
 * los caminos, incluidos los que todavía no existen.
 *
 * La clave la declara cada modelo en `CLAVE_CORRELATIVO` y no la recibe este
 * observer por constructor: `Model::observe()` se queda con el nombre de la
 * clase y descarta la instancia, así que el contenedor la vuelve a construir
 * sin argumentos.
 */
class CodigoCorrelativoObserver
{
    public function created(Model $registro): void
    {
        Correlativo::sincronizar($registro::CLAVE_CORRELATIVO, $registro->codigo);
    }
}
