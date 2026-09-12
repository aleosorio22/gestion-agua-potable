<?php

namespace App\Filament\Admin\Support;

use App\Models\Documento;
use App\Services\MetadatosDeArchivo;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class AccionesDocumento
{
    public static function descargar(): Action
    {
        return Action::make('descargar')
            ->label('Descargar')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->url(fn (Documento $record): string => route('documentos.descargar', $record), shouldOpenInNewTab: true);
    }

    /**
     * Recalcula el hash del archivo en disco y lo compara con el que se guardó
     * al archivarlo.
     *
     * Es para lo que existe la columna: sin esto, «tenemos la escritura
     * escaneada» es un acto de fe. Con esto se puede afirmar que el archivo es
     * exactamente el que se cargó ese día.
     */
    public static function verificarIntegridad(): Action
    {
        return Action::make('verificarIntegridad')
            ->label('Verificar integridad')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->color('gray')
            ->action(function (Documento $record): void {
                $metadatos = app(MetadatosDeArchivo::class);

                if (! Storage::disk($record->disco)->exists($record->ruta)) {
                    Notification::make()
                        ->danger()
                        ->title('El archivo no está en el almacenamiento')
                        ->body('El registro existe pero el archivo no. Vuelva a cargarlo.')
                        ->persistent()
                        ->send();

                    return;
                }

                if ($metadatos->coincide($record->disco, $record->ruta, $record->hash_sha256)) {
                    Notification::make()
                        ->success()
                        ->title('El documento está intacto')
                        ->body('El archivo es exactamente el que se cargó el '.$record->created_at->format('d/m/Y').'.')
                        ->send();

                    return;
                }

                Notification::make()
                    ->danger()
                    ->title('El archivo no coincide con el original')
                    ->body('Fue reemplazado o se dañó después de cargarlo. No lo use como respaldo hasta aclararlo.')
                    ->persistent()
                    ->send();
            });
    }

    /**
     * Borrar el registro sin borrar el archivo dejaría basura en disco para
     * siempre, y el expediente no tiene baja lógica donde esconderla.
     */
    public static function eliminar(): DeleteAction
    {
        return DeleteAction::make()
            ->modalDescription('Se elimina el registro y también el archivo del almacenamiento. No se puede deshacer.')
            ->after(function (Documento $record): void {
                Storage::disk($record->disco)->delete($record->ruta);
            });
    }
}
