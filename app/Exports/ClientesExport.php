<?php

namespace App\Exports;

use App\Models\Cliente;
use Illuminate\Support\Enumerable;

class ClientesExport extends ReporteExport
{
    public function collection(): Enumerable
    {
        return Cliente::query()->withCount('contadores')->orderBy('nombre')->get();
    }

    public function headings(): array
    {
        return ['Código', 'Nombre', 'DPI', 'NIT', 'Teléfono', 'Correo', 'Dirección', 'Servicios', 'Estado'];
    }

    /** @param Cliente $cliente */
    public function map($cliente): array
    {
        return [
            $cliente->codigo,
            $cliente->nombre,
            $cliente->dpi,
            $cliente->nit,
            $cliente->telefono,
            $cliente->email,
            $cliente->direccion_notificacion,
            $cliente->contadores_count,
            ucfirst($cliente->estado),
        ];
    }

    /** DPI, NIT y teléfono son identificadores: Excel les comería los ceros. */
    protected function columnasDeTexto(): array
    {
        return [0, 2, 3, 4];
    }
}
