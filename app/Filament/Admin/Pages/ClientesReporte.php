<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Enums\GrupoNavegacion;
use BackedEnum;
use UnitEnum;
use Filament\Support\Icons\Heroicon;
use App\Models\Cliente;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;

class ClientesReporte extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Reportes;

    protected static ?string $navigationLabel = 'Reporte de Clientes';

    protected static ?string $slug = 'reporte-clientes';

    protected string $view = 'filament.admin.pages.clientes-reporte';

    public function getHeaderActions(): array
    {
        return [
            Action::make('descargarPDF')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('generarPDF'),
        ];
    }

    public function generarPDF()
    {
        $clientes = Cliente::orderBy('nombre')->get();

        $pdf = Pdf::loadView('reportes.clientes', [
            'clientes' => $clientes,
            'fecha' => now(),
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'reporte-clientes-' . now()->format('Y-m-d') . '.pdf'
        );
    }
}
