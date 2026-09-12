<?php

namespace App\Exports;

use App\Models\Pago;
use Illuminate\Support\Enumerable;

class CorteDeCajaExport extends ReporteExport
{
    public function collection(): Enumerable
    {
        return Pago::query()
            ->vigentes()
            // La hora va explícita: el cast `date` guarda «… 00:00:00» y sin
            // ella se pierde lo cobrado el último día del rango.
            ->whereBetween('fecha_pago', [$this->desde().' 00:00:00', $this->hasta().' 23:59:59'])
            ->with(['boleta.cliente', 'metodoPago', 'usuario'])
            ->orderBy('fecha_pago')
            ->get();
    }

    public function headings(): array
    {
        return ['Recibo', 'Fecha', 'Cliente', 'Boleta', 'Método', 'Referencia', 'Cobró', 'Monto'];
    }

    /** @param Pago $pago */
    public function map($pago): array
    {
        return [
            $pago->folio,
            $pago->fecha_pago?->format('d/m/Y'),
            $pago->boleta?->cliente?->nombre,
            $pago->boleta?->folio,
            $pago->metodoPago?->nombre,
            $pago->referencia,
            $pago->usuario?->name,
            (float) $pago->monto,
        ];
    }

    protected function columnasDeTexto(): array
    {
        return [0, 3, 5];
    }
}
