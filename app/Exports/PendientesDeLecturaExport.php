<?php

namespace App\Exports;

use App\Models\Contador;
use Illuminate\Support\Enumerable;

class PendientesDeLecturaExport extends ReporteExport
{
    public function collection(): Enumerable
    {
        $periodo = $this->periodo();

        if ($periodo === null) {
            return collect();
        }

        $sector = $this->sector();

        return Contador::query()
            ->activos()
            ->sinLecturaEn($periodo->id)
            ->when($sector, fn ($consulta) => $consulta->whereHas(
                'predio',
                fn ($predio) => $predio->where('sector_id', $sector->id),
            ))
            ->with(['cliente', 'predio.sector'])
            ->get()
            ->sortBy(fn (Contador $c): int => $c->predio?->sector?->orden ?? 999)
            ->values();
    }

    public function headings(): array
    {
        return ['Contador', 'Titular', 'Dirección', 'Sector', 'Última lectura'];
    }

    /** @param Contador $contador */
    public function map($contador): array
    {
        return [
            $contador->codigo,
            $contador->cliente?->nombre,
            $contador->predio?->direccion_completa,
            $contador->predio?->sector?->nombre ?? 'Sin sector',
            (float) ($contador->ultimaLectura()?->lectura_actual ?? 0),
        ];
    }
}
