<?php

namespace App\Filament\Admin\Resources\Lecturas\Pages;

use App\Filament\Admin\Resources\Lecturas\LecturaResource;
use App\Models\Lectura;
use App\Models\Periodo;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLecturas extends ListRecords
{
    protected static string $resource = LecturaResource::class;

    public function getTitle(): string
    {
        return 'Lecturas';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Registrar lectura'),
        ];
    }

    /**
     * Sin período abierto el lector no puede hacer nada: conviene que la
     * pantalla lo diga en vez de mostrar un listado vacío sin explicación.
     */
    public function getSubheading(): ?string
    {
        if (! Periodo::abiertos()->exists()) {
            return 'No hay ningún período abierto. Abra el ciclo del mes para poder registrar lecturas.';
        }

        $periodo = Periodo::vigente();

        $sinFacturar = $periodo
            ? Lectura::where('periodo_id', $periodo->id)->doesntHave('boleta')->count()
            : 0;

        // El olvido tiene que verse: una lectura sin boleta es trabajo de campo
        // hecho que la oficina no cobró.
        return $sinFacturar > 0
            ? "{$sinFacturar} ".($sinFacturar === 1 ? 'lectura de este período todavía no tiene boleta.' : 'lecturas de este período todavía no tienen boleta.')
                .' Selecciónelas y use «Emitir boletas» para facturarlas juntas.'
            : null;
    }
}
