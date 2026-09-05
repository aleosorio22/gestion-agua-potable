<?php

namespace App\Filament\Admin\Resources\Sectores\Pages;

use App\Filament\Admin\Resources\Sectores\SectorResource;
use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Resources\Pages\EditRecord;

class EditSector extends EditRecord
{
    protected static string $resource = SectorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesCatalogo::eliminar(),
        ];
    }
}
