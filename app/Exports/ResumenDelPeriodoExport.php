<?php

namespace App\Exports;

use App\Models\Boleta;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Pago;
use Illuminate\Support\Enumerable;

class ResumenDelPeriodoExport extends ReporteExport
{
    public function collection(): Enumerable
    {
        $periodo = $this->periodo();

        if ($periodo === null) {
            return collect();
        }

        $boletas = Boleta::query()->where('periodo_id', $periodo->id)->vigentes()->get();
        $lecturas = Lectura::query()->where('periodo_id', $periodo->id)->get();

        $cobrado = Pago::query()
            ->vigentes()
            ->whereIn('boleta_id', $boletas->pluck('id'))
            ->sum('monto');

        return collect([
            ['concepto' => 'Período', 'valor' => $periodo->etiqueta_larga],
            ['concepto' => 'Contadores activos', 'valor' => Contador::query()->activos()->count()],
            ['concepto' => 'Lecturas tomadas', 'valor' => $lecturas->count()],
            ['concepto' => 'Consumo total m³', 'valor' => round($lecturas->sum(fn (Lectura $l): float => (float) $l->consumo_m3), 2)],
            ['concepto' => 'Consumo promedio m³', 'valor' => round((float) $lecturas->avg(fn (Lectura $l): float => (float) $l->consumo_m3), 2)],
            ['concepto' => 'Boletas emitidas', 'valor' => $boletas->count()],
            ['concepto' => 'Facturado', 'valor' => round($boletas->sum(fn (Boleta $b): float => (float) $b->monto), 2)],
            ['concepto' => 'Cobrado', 'valor' => round((float) $cobrado, 2)],
            ['concepto' => 'Por cobrar', 'valor' => round($boletas->sum(fn (Boleta $b): float => $b->saldo), 2)],
            ['concepto' => 'Boletas anuladas', 'valor' => Boleta::query()->where('periodo_id', $periodo->id)->anuladas()->count()],
        ]);
    }

    public function headings(): array
    {
        return ['Concepto', 'Valor'];
    }

    /** @param array<string, mixed> $fila */
    public function map($fila): array
    {
        return [$fila['concepto'], $fila['valor']];
    }

    protected function columnasDeTexto(): array
    {
        return [];
    }
}
