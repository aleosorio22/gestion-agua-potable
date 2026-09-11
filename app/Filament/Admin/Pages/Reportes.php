<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Boleta;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use UnitEnum;

class Reportes extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Reportes;

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'reportes';

    protected string $view = 'filament.admin.pages.reportes';

    /**
     * Registro central de reportes disponibles. Agregar un reporte nuevo
     * es agregar una entrada aquí + su método privado correspondiente
     * (consultaClientes, consultaContadores, etc.) — no hay que tocar
     * generarPdf()/generarExcel() ni la vista.
     *
     * @return array<string, array{titulo: string, descripcion: string, icono: string}>
     */
    public function getReportes(): array
    {
        return [
            'clientes' => [
                'titulo' => 'Clientes',
                'descripcion' => 'Listado de clientes registrados',
                'icono' => 'heroicon-o-users',
            ],
            'contadores' => [
                'titulo' => 'Contadores',
                'descripcion' => 'Listado de contadores instalados',
                'icono' => 'heroicon-o-cpu-chip',
            ],
            'boletas' => [
                'titulo' => 'Boletas',
                'descripcion' => 'Facturación por período',
                'icono' => 'heroicon-o-receipt-percent',
            ],
            'mora' => [
                'titulo' => 'Cuentas por cobrar',
                'descripcion' => 'Boletas pendientes o vencidas',
                'icono' => 'heroicon-o-exclamation-triangle',
            ],
            // agregar más categorías aquí conforme se construyan
        ];
    }

    /**
     * Punto de entrada único para descargar PDF de cualquier categoría.
     * Llamado desde la vista con wire:click="generarPdf('clientes')".
     */
    public function generarPdf(string $reporte)
    {
        if (! array_key_exists($reporte, $this->getReportes())) {
            abort(404);
        }

        $metodoConsulta = 'consulta' . Str::studly($reporte);

        if (! method_exists($this, $metodoConsulta)) {
            abort(404, "No existe la consulta para el reporte [{$reporte}].");
        }

        $datos = $this->{$metodoConsulta}();

        $pdf = Pdf::loadView("reportes.{$reporte}", array_merge($datos, [
            'fecha' => now(),
        ]));

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "reporte-{$reporte}-" . now()->format('Y-m-d') . '.pdf'
        );
    }

    /**
     * Punto de entrada único para descargar Excel de cualquier categoría.
     * Requiere maatwebsite/excel (composer require maatwebsite/excel).
     */
    public function generarExcel(string $reporte)
    {
        if (! array_key_exists($reporte, $this->getReportes())) {
            abort(404);
        }

        $claseExport = 'App\\Exports\\' . Str::studly($reporte) . 'Export';

        if (! class_exists($claseExport)) {
            abort(404, "No existe la clase de exportación [{$claseExport}].");
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new $claseExport(),
            "reporte-{$reporte}-" . now()->format('Y-m-d') . '.xlsx'
        );
    }

    // ── Consultas por categoría ──────────────────────────────────────

    private function consultaClientes(): array
    {
        return [
            'clientes' => Cliente::orderBy('nombre')->get(),
        ];
    }

    private function consultaContadores(): array
    {
        return [
            'contadores' => Contador::with(['cliente', 'predio'])->get(),
        ];
    }

    private function consultaBoletas(): array
    {
        return [
            'boletas' => Boleta::with('cliente')
                ->whereBetween('created_at', [now()->startOfMonth(), now()])
                ->get(),
        ];
    }

    private function consultaMora(): array
    {
        return [
            'boletas' => Boleta::with('cliente')
                ->whereIn('estado', ['pendiente', 'vencida'])
                ->get(),
        ];
    }
}
