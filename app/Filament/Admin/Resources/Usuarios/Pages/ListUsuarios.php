<?php

namespace App\Filament\Admin\Resources\Usuarios\Pages;

use App\Filament\Admin\Resources\Usuarios\UsuarioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsuarios extends ListRecords
{
    protected static string $resource = UsuarioResource::class;

    public function getTitle(): string
    {
        return 'Usuarios';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo usuario'),
        ];
    }
}
