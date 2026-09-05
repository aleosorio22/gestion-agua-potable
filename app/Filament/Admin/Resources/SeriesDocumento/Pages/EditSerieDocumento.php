<?php

namespace App\Filament\Admin\Resources\SeriesDocumento\Pages;

use App\Filament\Admin\Resources\SeriesDocumento\SerieDocumentoResource;
use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Resources\Pages\EditRecord;

class EditSerieDocumento extends EditRecord
{
    protected static string $resource = SerieDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesCatalogo::eliminar(),
        ];
    }
}
