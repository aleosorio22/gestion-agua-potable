<?php

namespace App\Exports;

use App\Models\Boleta;
use Illuminate\Support\Enumerable;

class BoletasExport extends ReporteExport
{
    public function collection(): Enumerable
    {
        $periodo = $this->periodo();

        if ($periodo === null) {
            return collect();
        }

        return Boleta::query()
            ->where('periodo_id', $periodo->id)
            ->vigentes()
            ->with(['cliente', 'periodo'])
            ->orderBy('numero')
            ->get();
    }

    public function headings(): array
    {
        return ['Folio', 'Cliente', 'Período', 'Consumo m³', 'Cuota', 'Exceso', 'Total', 'Saldo', 'Estado'];
    }

    /** @param Boleta $boleta */
    public function map($boleta): array
    {
        return [
            $boleta->folio,
            $boleta->cliente?->nombre,
            $boleta->periodo?->etiqueta,
            (float) $boleta->consumo_m3,
            (float) $boleta->monto_base,
            (float) $boleta->monto_excedente,
            (float) $boleta->monto,
            $boleta->saldo,
            ucfirst($boleta->estado),
        ];
    }

    protected function columnasDeTexto(): array
    {
        return [0, 2];
    }
}
