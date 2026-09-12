<?php

namespace App\Exports;

use App\Models\Cliente;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ClientesExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithCustomValueBinder
{
    /** Columnas (0-indexed, según el orden de map()) que deben forzarse a texto. */
    private const COLUMNAS_TEXTO = [2, 3, 4]; // nit, dpi, telefono

    public function collection(): Enumerable
    {
        return Cliente::orderBy('nombre')->get();
    }

    public function headings(): array
    {
        return ['Código', 'Nombre', 'NIT', 'DPI', 'Teléfono', 'Email', 'Dirección', 'Estado'];
    }

    public function map($cliente): array
    {
        return [
            $cliente->codigo,
            $cliente->nombre,
            $cliente->nit,
            $cliente->dpi,
            $cliente->telefono,
            $cliente->email,
            $cliente->direccion_notificacion,
            $cliente->estado,
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
