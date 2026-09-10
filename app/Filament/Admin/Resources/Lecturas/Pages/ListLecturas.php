<?php

namespace App\Filament\Admin\Resources\Lecturas\Pages;

use App\Filament\Admin\Resources\Lecturas\LecturaResource;
use App\Models\Periodo;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLecturas extends ListRecords
{
    protected static string $resource = LecturaResource::class;

    public function getTitle(): string
    {
        return 'Lecturas';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Registrar lectura'),
        ];
    }

    /**
     * Sin período abierto el lector no puede hacer nada: conviene que la
     * pantalla lo diga en vez de mostrar un listado vacío sin explicación.
     */
    public function getSubheading(): ?string
    {
        return Periodo::abiertos()->exists()
            ? null
            : 'No hay ningún período abierto. Abra el ciclo del mes para poder registrar lecturas.';
    }
}
