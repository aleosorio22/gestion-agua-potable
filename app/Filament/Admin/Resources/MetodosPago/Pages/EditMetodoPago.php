<?php

namespace App\Filament\Admin\Resources\MetodosPago\Pages;

use App\Filament\Admin\Resources\MetodosPago\MetodoPagoResource;
use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Resources\Pages\EditRecord;

class EditMetodoPago extends EditRecord
{
    protected static string $resource = MetodoPagoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesCatalogo::eliminar(),
        ];
    }
}
