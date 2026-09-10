<?php

namespace App\Filament\Admin\Resources\Predios\Pages;

use App\Filament\Admin\Resources\Predios\PredioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPredios extends ListRecords
{
    protected static string $resource = PredioResource::class;

    public function getTitle(): string
    {
        return 'Predios';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo predio'),
        ];
    }
}
