<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Lecturas\LecturaResource;
use App\Filament\Admin\Resources\Predios\PredioResource;
use App\Models\Lectura;
use App\Models\Predio;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Lo que quedó a medias y nadie está mirando.
 *
 * Son dos olvidos que no producen ningún error y por eso se acumulan en
 * silencio: trabajo de campo que nunca se cobró, y servicio conectado sin nada
 * que respalde el derecho del titular. Puestos acá, se ven todos los días.
 */
class TrabajoPendiente extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        return [
            $this->lecturasSinFacturar(),
            $this->prediosSinRespaldo(),
        ];
    }

    private function lecturasSinFacturar(): Stat
    {
        $cuantas = Lectura::query()->doesntHave('boleta')->count();

        return Stat::make('Lecturas sin facturar', (string) $cuantas)
            ->description($cuantas > 0 ? 'Se midieron pero no se cobraron' : 'Todo lo medido está facturado')
            ->descriptionIcon(Heroicon::OutlinedClipboardDocumentList)
            ->color($cuantas > 0 ? 'warning' : 'success')
            ->url($cuantas > 0 ? LecturaResource::getUrl('index') : null);
    }

    private function prediosSinRespaldo(): Stat
    {
        $cuantos = Predio::query()->has('contadores')->doesntHave('documentos')->count();

        return Stat::make('Predios sin respaldo', (string) $cuantos)
            ->description($cuantos > 0 ? 'Con servicio y sin escritura cargada' : 'Todo predio servido tiene documento')
            ->descriptionIcon(Heroicon::OutlinedPaperClip)
            ->color($cuantos > 0 ? 'warning' : 'success')
            ->url($cuantos > 0 ? PredioResource::getUrl('index') : null);
    }
}
