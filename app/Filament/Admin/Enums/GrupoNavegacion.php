<?php

namespace App\Filament\Admin\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Grupos del menú lateral. Van en un enum para que la etiqueta se escriba una
 * sola vez: un grupo mal tecleado en un Resource lo saca del menú sin error.
 */
enum GrupoNavegacion: string implements HasLabel
{
    case Catalogos = 'catalogos';

    public function getLabel(): string
    {
        return match ($this) {
            self::Catalogos => 'Catálogos',
        };
    }
}
