<?php

namespace App\Exports;

use App\Models\Boleta;
use App\Models\Pago;
use Illuminate\Support\Enumerable;

/**
 * Boletas anuladas y pagos revertidos en una sola hoja.
 *
 * Van juntos y con una columna que los distingue porque quien audita busca
 * «qué se dejó sin efecto», no un tipo de documento en particular.
 */
class AnulacionesExport extends ReporteExport
{
    public function collection(): Enumerable
    {
        $desde = $this->desde().' 00:00:00';
        $hasta = $this->hasta().' 23:59:59';

        $boletas = Boleta::query()
            ->anuladas()
            ->whereBetween('anulada_en', [$desde, $hasta])
            ->with(['cliente', 'periodo', 'anuladaPor'])
            ->get()
            ->map(fn (Boleta $boleta): array => [
                'tipo' => 'Boleta anulada',
                'folio' => $boleta->folio,
                'cliente' => $boleta->cliente?->nombre,
                'monto' => (float) $boleta->monto,
                'cuando' => $boleta->anulada_en,
                'quien' => $boleta->anuladaPor?->name,
                'motivo' => $boleta->motivo_anulacion,
            ]);

        $pagos = Pago::query()
            ->revertidos()
            ->whereBetween('revertido_en', [$desde, $hasta])
            ->with(['boleta.cliente', 'revertidoPor'])
            ->get()
            ->map(fn ($pago): array => [
                'tipo' => 'Pago revertido',
                'folio' => $pago->folio,
                'cliente' => $pago->boleta?->cliente?->nombre,
                'monto' => (float) $pago->monto,
                'cuando' => $pago->revertido_en,
                'quien' => $pago->revertidoPor?->name,
                'motivo' => $pago->motivo_reverso,
            ]);

        return $boletas->concat($pagos)->sortByDesc('cuando')->values();
    }

    public function headings(): array
    {
        return ['Tipo', 'Folio', 'Cliente', 'Monto', 'Fecha', 'Responsable', 'Motivo'];
    }

    /** @param array<string, mixed> $fila */
    public function map($fila): array
    {
        return [
            $fila['tipo'],
            $fila['folio'],
            $fila['cliente'],
            $fila['monto'],
            $fila['cuando']?->format('d/m/Y H:i'),
            $fila['quien'],
            $fila['motivo'],
        ];
    }

    protected function columnasDeTexto(): array
    {
        return [1];
    }
}
