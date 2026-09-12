<?php

namespace App\Exports;

use App\Models\Boleta;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class MoraExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithCustomValueBinder
{
    private const COLUMNAS_TEXTO = [0]; // numero

    public function collection(): Enumerable
    {
        return Boleta::vencidas()->with(['cliente', 'periodo'])->orderBy('fecha_vencimiento')->get();
    }

    public function headings(): array
    {
        return ['No. Boleta', 'Cliente', 'Período', 'Saldo', 'Vencimiento', 'Días de atraso'];
    }

    public function map($boleta): array
    {
        return [
            $boleta->numero,
            $boleta->cliente?->nombre,
            $boleta->periodo?->etiqueta,
            $boleta->saldo,
            $boleta->fecha_vencimiento?->format('d/m/Y'),
            $boleta->fecha_vencimiento->diffInDays(now()),
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
