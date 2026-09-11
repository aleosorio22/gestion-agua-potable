<?php

namespace App\Filament\Admin\Resources\Documentos\Pages;

use App\Filament\Admin\Resources\Documentos\DocumentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentos extends ListRecords
{
    protected static string $resource = DocumentoResource::class;

    public function getTitle(): string
    {
        return 'Documentos';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Cargar documento'),
        ];
    }
}
