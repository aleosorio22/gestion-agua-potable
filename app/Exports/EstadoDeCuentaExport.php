<?php

namespace App\Exports;

use App\Models\Cliente;
use Illuminate\Support\Carbon;
use Illuminate\Support\Enumerable;

class EstadoDeCuentaExport extends ReporteExport
{
    public function collection(): Enumerable
    {
        return Cliente::query()
            ->conEstadoDeCuenta()
            ->withCount('contadores')
            ->orderByDesc('deuda')
            ->get()
            ->filter(fn (Cliente $cliente): bool => $cliente->deuda_total > 0);
    }

    public function headings(): array
    {
        return ['Código', 'Cliente', 'Teléfono', 'Servicios', 'Vence lo más antiguo', 'Estado', 'Debe'];
    }

    /** @param Cliente $cliente */
    public function map($cliente): array
    {
        return [
            $cliente->codigo,
            $cliente->nombre,
            $cliente->telefono,
            $cliente->contadores_count,
            $cliente->vence_mas_antigua
                ? Carbon::parse($cliente->vence_mas_antigua)->format('d/m/Y')
                : null,
            $cliente->estado_de_cuenta === 'vencido' ? 'Vencido' : 'Pendiente',
            $cliente->deuda_total,
        ];
    }

    protected function columnasDeTexto(): array
    {
        return [0, 2];
    }
}
