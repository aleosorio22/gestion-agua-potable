<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Boleta;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Pago;
use App\Models\Periodo;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Los cuatro números con los que la oficina sabe cómo va el mes.
 */
class ResumenCobranza extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $periodo = Periodo::vigente();

        return [
            Stat::make('Cobrado este mes', 'Q'.number_format($this->cobradoEsteMes(), 2))
                ->description('Pagos vigentes de '.now()->format('m/Y'))
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success'),

            Stat::make('Por cobrar', 'Q'.number_format(Boleta::saldoPendienteTotal(), 2))
                ->description('Saldo de todas las boletas vigentes')
                ->descriptionIcon(Heroicon::OutlinedDocumentCurrencyDollar)
                ->color('warning'),

            Stat::make('Boletas vencidas', (string) Boleta::query()->vencidas()->count())
                ->description('Pasaron su fecha de pago')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),

            $this->avanceDeLaRuta($periodo),
        ];
    }

    private function cobradoEsteMes(): float
    {
        return round((float) Pago::query()
            ->vigentes()
            ->whereYear('fecha_pago', now()->year)
            ->whereMonth('fecha_pago', now()->month)
            ->sum('monto'), 2);
    }

    private function avanceDeLaRuta(?Periodo $periodo): Stat
    {
        if ($periodo === null) {
            return Stat::make('Ruta de lectura', 'Sin período')
                ->description('Abra el ciclo del mes para poder leer')
                ->descriptionIcon(Heroicon::OutlinedMapPin)
                ->color('gray');
        }

        $total = Contador::query()->activos()->count();
        $leidos = Lectura::query()->where('periodo_id', $periodo->id)->count();

        return Stat::make('Ruta de '.$periodo->etiqueta, "{$leidos} de {$total}")
            ->description($leidos >= $total && $total > 0 ? 'Recorrido completo' : 'Contadores leídos')
            ->descriptionIcon(Heroicon::OutlinedMapPin)
            ->color($total > 0 && $leidos >= $total ? 'success' : 'info');
    }
}
