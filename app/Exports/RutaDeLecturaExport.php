<?php

namespace App\Exports;

use App\Models\Contador;
use Illuminate\Support\Enumerable;

class RutaDeLecturaExport extends ReporteExport
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
            ->with(['cliente', 'predio.sector', 'paja'])
            ->get()
            ->sortBy([
                fn (Contador $c): int => $c->predio?->sector?->orden ?? 999,
                fn (Contador $c): string => (string) $c->predio?->numero_casa,
            ])
            ->values();
    }

    public function headings(): array
    {
        // La última va vacía a propósito: es donde se anota la lectura.
        return ['Contador', 'Titular', 'Dirección', 'Sector', 'Paja', 'Lectura anterior', 'Lectura actual'];
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
            (float) ($contador->ultimaLectura()?->lectura_actual ?? 0),
            null,
        ];
    }
}
