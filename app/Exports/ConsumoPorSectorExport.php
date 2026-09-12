<?php

namespace App\Exports;

use App\Models\Lectura;
use Illuminate\Support\Enumerable;

class ConsumoPorSectorExport extends ReporteExport
{
    public function collection(): Enumerable
    {
        $periodo = $this->periodo();

        if ($periodo === null) {
            return collect();
        }

        return Lectura::query()
            ->where('periodo_id', $periodo->id)
            ->with('contador.predio.sector')
            ->get()
            ->groupBy(fn (Lectura $l): string => $l->contador?->predio?->sector?->nombre ?? 'Sin sector')
            ->map(fn ($lecturas, string $sector): array => [
                'sector' => $sector,
                'servicios' => $lecturas->count(),
                'consumo' => $lecturas->sum(fn (Lectura $l): float => (float) $l->consumo_m3),
                'promedio' => round($lecturas->avg(fn (Lectura $l): float => (float) $l->consumo_m3), 2),
                'mayor' => $lecturas->max(fn (Lectura $l): float => (float) $l->consumo_m3),
            ])
            ->sortByDesc('consumo')
            ->values();
    }

    public function headings(): array
    {
        return ['Sector', 'Servicios leídos', 'Consumo total m³', 'Promedio m³', 'Mayor consumo m³'];
    }

    /** @param array<string, mixed> $fila */
    public function map($fila): array
    {
        return [
            $fila['sector'],
            $fila['servicios'],
            $fila['consumo'],
            $fila['promedio'],
            $fila['mayor'],
        ];
    }

    protected function columnasDeTexto(): array
    {
        return [];
    }
}
