<?php

namespace App\Filament\Admin\Resources\Tarifas\Pages;

use App\Filament\Admin\Resources\Tarifas\TarifaResource;
use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Resources\Pages\EditRecord;

class EditTarifa extends EditRecord
{
    protected static string $resource = TarifaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesCatalogo::eliminar(),
        ];
    }
}
