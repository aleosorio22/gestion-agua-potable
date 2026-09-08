<?php

namespace App\Filament\Admin\Support;

/**
 * Los meses en español para los selectores. Van aquí y no en cada formulario
 * porque `Carbon::locale('es')` depende de la configuración regional del
 * servidor, y en un despliegue nuevo eso sale en inglés sin avisar.
 */
class MesesDelAnio
{
    /**
     * @return array<int, string>
     */
    public static function opciones(): array
    {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
    }

    public static function nombre(int $mes): string
    {
        return static::opciones()[$mes] ?? (string) $mes;
    }
}
