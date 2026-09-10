<?php

namespace App\Filament\Admin\Resources\Boletas\Pages;

use App\Filament\Admin\Resources\Boletas\BoletaResource;
use Filament\Resources\Pages\ListRecords;

class ListBoletas extends ListRecords
{
    protected static string $resource = BoletaResource::class;

    public function getTitle(): string
    {
        return 'Boletas';
    }
}
