<?php

namespace App\Filament\Admin\Resources\TiposDocumento\Pages;

use App\Filament\Admin\Resources\TiposDocumento\TipoDocumentoResource;
use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Resources\Pages\EditRecord;

class EditTipoDocumento extends EditRecord
{
    protected static string $resource = TipoDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesCatalogo::eliminar(),
        ];
    }
}
