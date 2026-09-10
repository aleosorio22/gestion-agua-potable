<?php

namespace App\Filament\Admin\Resources\Contadores\Pages;

use App\Filament\Admin\Resources\Contadores\ContadorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContadores extends ListRecords
{
    protected static string $resource = ContadorResource::class;

    public function getTitle(): string
    {
        return 'Contadores';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo contador'),
        ];
    }
}
