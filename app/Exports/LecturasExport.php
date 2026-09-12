<?php

namespace App\Exports;

use App\Models\Lectura;
use Illuminate\Support\Enumerable;

class LecturasExport extends ReporteExport
{
    public function collection(): Enumerable
    {
        $periodo = $this->periodo();

        if ($periodo === null) {
            return collect();
        }

        return Lectura::query()
            ->where('periodo_id', $periodo->id)
            ->with(['contador.predio.sector', 'contador.cliente', 'usuario'])
            ->get()
            ->sortBy(fn (Lectura $l): int => $l->contador?->predio?->sector?->orden ?? 999)
            ->values();
    }

    public function headings(): array
    {
        return ['Contador', 'Titular', 'Sector', 'Visita', 'Anterior', 'Actual', 'Consumo m³', 'Lector'];
    }

    /** @param Lectura $lectura */
    public function map($lectura): array
    {
        return [
            $lectura->contador?->codigo,
            $lectura->contador?->cliente?->nombre,
            $lectura->contador?->predio?->sector?->nombre ?? 'Sin sector',
            $lectura->fecha_lectura?->format('d/m/Y'),
            (float) $lectura->lectura_anterior,
            (float) $lectura->lectura_actual,
            (float) $lectura->consumo_m3,
            $lectura->usuario?->name,
        ];
    }
}
