<?php

namespace App\Filament\Admin\Resources\TiposDocumento\Pages;

use App\Filament\Admin\Resources\TiposDocumento\TipoDocumentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTiposDocumento extends ListRecords
{
    protected static string $resource = TipoDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
