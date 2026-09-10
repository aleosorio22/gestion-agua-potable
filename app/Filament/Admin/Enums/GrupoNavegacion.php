<?php

namespace App\Filament\Admin\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Grupos del menú lateral. Van en un enum para que la etiqueta se escriba una
 * sola vez: un grupo mal tecleado en un Resource lo saca del menú sin error.
 */
enum GrupoNavegacion: string implements HasLabel
{
    case Padron = 'padron';
    case Operacion = 'operacion';
    case Catalogos = 'catalogos';
    case Reportes = 'reportes';

    public function getLabel(): string
    {
        return match ($this) {
            self::Padron => 'Padrón',
            self::Operacion => 'Operación',
            self::Catalogos => 'Catálogos',
            self::Reportes => 'Reportes'
        };
    }
}
