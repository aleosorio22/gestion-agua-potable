<?php

namespace App\Filament\Admin\Resources\Sectores\Pages;

use App\Filament\Admin\Resources\Sectores\SectorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSectores extends ListRecords
{
    protected static string $resource = SectorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
