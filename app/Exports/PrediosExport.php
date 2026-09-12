<?php

namespace App\Exports;

use App\Models\Predio;
use Illuminate\Support\Enumerable;

class PrediosExport extends ReporteExport
{
    public function collection(): Enumerable
    {
        return Predio::query()
            ->with('sector')
            ->withCount(['contadores', 'documentos'])
            ->get()
            ->sortBy(fn (Predio $p): int => $p->sector?->orden ?? 999)
            ->values();
    }

    public function headings(): array
    {
        return ['Sector', 'Dirección', 'Referencia', 'Contadores', 'Documentos', 'Respaldo'];
    }

    /** @param Predio $predio */
    public function map($predio): array
    {
        return [
            $predio->sector?->nombre ?? 'Sin sector',
            $predio->direccion_completa,
            $predio->referencia,
            $predio->contadores_count,
            $predio->documentos_count,
            $predio->documentos_count > 0 ? 'Sí' : ($predio->contadores_count > 0 ? 'FALTA' : '—'),
        ];
    }

    protected function columnasDeTexto(): array
    {
        return [];
    }
}
