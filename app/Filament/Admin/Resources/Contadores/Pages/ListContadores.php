<?php

namespace App\Filament\Admin\Resources\Contadores\Pages;

use App\Filament\Admin\Resources\Contadores\ContadorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Admin\Concerns\TieneBotonImprimir;

class ListContadores extends ListRecords
{
    use TieneBotonImprimir;

    protected static string $resource = ContadorResource::class;

    public function getTitle(): string
    {
        return 'Contadores';
    }

    protected function getHeaderActions(): array
    {
        return [
        $this->getImprimirAction(),
            CreateAction::make()
                ->label('Nuevo contador'),
        ];
    }
}
