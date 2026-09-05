<?php

namespace App\Filament\Admin\Resources\Pajas\Pages;

use App\Filament\Admin\Resources\Pajas\PajaResource;
use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Resources\Pages\EditRecord;

class EditPaja extends EditRecord
{
    protected static string $resource = PajaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesCatalogo::eliminar(),
        ];
    }
}
