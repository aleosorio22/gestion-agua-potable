<?php

namespace App\Filament\Admin\Support;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/**
 * Borrado de catálogos con la misma regla en todas las pantallas: si la fila
 * ya respalda documentos, se desactiva en lugar de borrarse.
 *
 * El botón se deshabilita para que no haya que descubrirlo intentándolo, y el
 * `before()` queda como red por si el uso aparece entre que se pintó la tabla
 * y se confirmó el modal.
 */
class AccionesCatalogo
{
    public static function eliminar(): DeleteAction
    {
        return DeleteAction::make()
            ->disabled(fn (Model $record): bool => $record->estaEnUso())
            ->tooltip(fn (Model $record): ?string => $record->estaEnUso()
                ? "En uso por {$record->motivoDeUso()}. Desactívelo en lugar de eliminarlo."
                : null)
            ->before(function (DeleteAction $action, Model $record): void {
                $motivo = $record->motivoDeUso();

                if ($motivo === null) {
                    return;
                }

                Notification::make()
                    ->danger()
                    ->title('No se puede eliminar')
                    ->body("Está en uso por {$motivo}. Desactívelo en lugar de eliminarlo.")
                    ->send();

                $action->cancel();
            });
    }
}
