<?php

namespace App\Exports;

use App\Models\Predio;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PrediosExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Enumerable
    {
        return Predio::with('sector')
            ->withCount('contadores')
            ->get()
            ->map(function (Predio $predio) {
                $predio->total_clientes = $predio->clientes()->count();

                return $predio;
            });
    }

    public function headings(): array
    {
        return ['Sector', 'Dirección', 'Contadores', 'Clientes'];
    }

    public function map($predio): array
    {
        return [
            $predio->sector?->nombre,
            $predio->direccion_completa,
            $predio->contadores_count,
            $predio->total_clientes,
        ];
    }
}
