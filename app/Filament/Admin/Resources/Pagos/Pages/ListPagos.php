<?php

namespace App\Filament\Admin\Resources\Pagos\Pages;

use App\Filament\Admin\Resources\Pagos\PagoResource;
use Filament\Resources\Pages\ListRecords;

class ListPagos extends ListRecords
{
    protected static string $resource = PagoResource::class;

    public function getTitle(): string
    {
        return 'Pagos';
    }

    public function getSubheading(): ?string
    {
        return 'Los cobros se registran desde la boleta del vecino, en la pantalla de Boletas.';
    }
}
