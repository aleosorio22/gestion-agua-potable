<?php

namespace App\Exports;

use App\Models\Contador;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class ContadoresExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithCustomValueBinder
{
    /** Columnas (0-indexed, según map()) que deben forzarse a texto. */
    private const COLUMNAS_TEXTO = [0]; // codigo

    public function collection(): Enumerable
    {
        return Contador::with(['cliente', 'predio', 'paja'])->orderBy('codigo')->get();
    }

    public function headings(): array
    {
        return ['Código', 'Cliente', 'Predio', 'Paja', 'Fecha instalación', 'Estado'];
    }

    public function map($contador): array
    {
        return [
            $contador->codigo,
            $contador->cliente?->nombre,
            $contador->predio?->direccion_completa,
            $contador->paja?->nombre,
            $contador->fecha_instalacion?->format('d/m/Y'),
            $contador->estado,
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
