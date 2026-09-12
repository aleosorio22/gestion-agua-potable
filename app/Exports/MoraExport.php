<?php

namespace App\Exports;

use App\Models\Boleta;
use Illuminate\Support\Enumerable;

class MoraExport extends ReporteExport
{
    public function collection(): Enumerable
    {
        return Boleta::query()
            ->vencidas()
            ->with(['cliente', 'periodo', 'lectura.contador'])
            ->orderBy('fecha_vencimiento')
            ->get();
    }

    public function headings(): array
    {
        return ['Boleta', 'Cliente', 'Contador', 'Período', 'Vencimiento', 'Días de atraso', 'Saldo'];
    }

    /** @param Boleta $boleta */
    public function map($boleta): array
    {
        return [
            $boleta->folio,
            $boleta->cliente?->nombre,
            $boleta->lectura?->contador?->codigo,
            $boleta->periodo?->etiqueta,
            $boleta->fecha_vencimiento?->format('d/m/Y'),
            (int) $boleta->fecha_vencimiento->diffInDays(now()),
            $boleta->saldo,
        ];
    }

    protected function columnasDeTexto(): array
    {
        return [0, 2, 3];
    }
}
