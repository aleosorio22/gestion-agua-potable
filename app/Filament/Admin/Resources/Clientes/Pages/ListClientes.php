<?php

namespace App\Filament\Admin\Resources\Clientes\Pages;

use App\Filament\Admin\Concerns\TieneBotonImprimir;
use App\Filament\Admin\Resources\Clientes\ClienteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClientes extends ListRecords
{
    use TieneBotonImprimir;

    protected static string $resource = ClienteResource::class;

    public function getTitle(): string
    {
        return 'Clientes';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getImprimirAction(),
            CreateAction::make()
                ->label('Nuevo cliente'),
        ];
    }
}
