<?php

namespace App\Exports;

use App\Models\Contador;
use Illuminate\Support\Enumerable;

class ContadoresExport extends ReporteExport
{
    public function collection(): Enumerable
    {
        $sector = $this->sector();

        return Contador::query()
            ->when($sector, fn ($consulta) => $consulta->whereHas(
                'predio',
                fn ($predio) => $predio->where('sector_id', $sector->id),
            ))
            ->with(['cliente', 'predio.sector', 'paja'])
            ->orderBy('codigo')
            ->get();
    }

    public function headings(): array
    {
        return ['Código', 'Titular', 'Dirección', 'Sector', 'Paja', 'Instalado', 'Estado'];
    }

    /** @param Contador $contador */
    public function map($contador): array
    {
        return [
            $contador->codigo,
            $contador->cliente?->nombre,
            $contador->predio?->direccion_completa,
            $contador->predio?->sector?->nombre ?? 'Sin sector',
            $contador->paja?->nombre,
            $contador->fecha_instalacion?->format('d/m/Y'),
            ucfirst($contador->estado),
        ];
    }
}
