<?php

namespace App\Filament\Admin\Resources\SeriesDocumento\Pages;

use App\Filament\Admin\Resources\SeriesDocumento\SerieDocumentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSeriesDocumento extends ListRecords
{
    protected static string $resource = SerieDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
