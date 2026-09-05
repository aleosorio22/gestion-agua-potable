<?php

namespace App\Filament\Admin\Resources\Pajas\Pages;

use App\Filament\Admin\Resources\Pajas\PajaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPajas extends ListRecords
{
    protected static string $resource = PajaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
