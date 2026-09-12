<?php

namespace App\Exports;

use App\Models\Periodo;
use App\Models\Sector;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Base de las exportaciones a Excel.
 *
 * Resuelve una sola cosa, pero que aparece en todas: los códigos y folios como
 * «CLI-0001» o «00123» se ven como texto, y si Excel los interpreta pierde los
 * ceros a la izquierda o los convierte en fecha. Cada reporte declara qué
 * columnas son texto y esta clase se encarga del resto.
 */
abstract class ReporteExport extends DefaultValueBinder implements FromCollection, WithCustomValueBinder, WithHeadings, WithMapping
{
    /**
     * @param  array{periodo: ?Periodo, desde: string, hasta: string, sector: ?Sector}  $filtros
     */
    public function __construct(protected array $filtros = []) {}

    abstract public function collection(): Enumerable;

    /**
     * @return array<int, string>
     */
    abstract public function headings(): array;

    /**
     * Índices (base 0) de las columnas que Excel no debe interpretar.
     *
     * @return array<int, int>
     */
    protected function columnasDeTexto(): array
    {
        return [0];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        $columna = Coordinate::columnIndexFromString($cell->getColumn()) - 1;

        if (in_array($columna, $this->columnasDeTexto(), true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    protected function periodo(): ?Periodo
    {
        return $this->filtros['periodo'] ?? Periodo::vigente();
    }

    protected function sector(): ?Sector
    {
        return $this->filtros['sector'] ?? null;
    }

    protected function desde(): string
    {
        return $this->filtros['desde'] ?? now()->startOfMonth()->toDateString();
    }

    protected function hasta(): string
    {
        return $this->filtros['hasta'] ?? now()->toDateString();
    }
}
