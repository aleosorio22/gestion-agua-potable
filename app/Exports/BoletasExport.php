<?php

namespace App\Exports;

use App\Models\Periodo;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class BoletasExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithCustomValueBinder
{
    private const COLUMNAS_TEXTO = [0]; // numero

    public function collection(): Enumerable
    {
        $periodo = Periodo::vigente();

        return $periodo
            ? $periodo->boletas()->vigentes()->with('cliente')->orderBy('numero')->get()
            : collect();
    }

    public function headings(): array
    {
        return ['No.', 'Cliente', 'Consumo m³', 'Monto', 'Emisión', 'Vencimiento', 'Estado'];
    }

    public function map($boleta): array
    {
        return [
            $boleta->numero,
            $boleta->cliente?->nombre,
            $boleta->consumo_m3,
            $boleta->monto,
            $boleta->fecha_emision?->format('d/m/Y'),
            $boleta->fecha_vencimiento?->format('d/m/Y'),
            $boleta->estado,
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
