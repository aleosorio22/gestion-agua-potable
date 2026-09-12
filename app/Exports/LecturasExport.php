<?php

namespace App\Exports;

use App\Models\Periodo;
use App\Models\Lectura;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class LecturasExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithCustomValueBinder
{
    private const COLUMNAS_TEXTO = [1]; // contador codigo

    public function collection(): Enumerable
    {
        $periodo = Periodo::vigente();

        return $periodo
            ? Lectura::where('periodo_id', $periodo->id)
                ->with(['contador.predio.sector', 'contador.cliente', 'usuario'])
                ->get()
            : collect();
    }

    public function headings(): array
    {
        return ['Sector', 'Contador', 'Cliente', 'Lect. anterior', 'Lect. actual', 'Consumo m³', 'Fecha', 'Lector'];
    }

    public function map($lectura): array
    {
        return [
            $lectura->contador?->predio?->sector?->nombre,
            $lectura->contador?->codigo,
            $lectura->contador?->cliente?->nombre,
            $lectura->lectura_anterior,
            $lectura->lectura_actual,
            $lectura->consumo_m3,
            $lectura->fecha_lectura?->format('d/m/Y'),
            $lectura->usuario?->name,
        ];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        $columna = Coordinate::columnIndexFromString($cell->getColumn()) - 1;

        if (in_array($columna, self::COLUMNAS_TEXTO, true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
