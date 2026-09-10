<?php

namespace App\Filament\Admin\Support;

use App\Models\Periodo;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Cierre del ciclo mensual, con la misma advertencia en todas las pantallas.
 *
 * No hay acción de reabrir a propósito: si un período cerrado se puede volver a
 * abrir, cerrarlo deja de significar «este mes ya está liquidado» y las boletas
 * emitidas pierden el respaldo de que nadie tocó las lecturas después.
 */
class AccionCerrarPeriodo
{
    public static function make(): Action
    {
        return Action::make('cerrar')
            ->label('Cerrar período')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('warning')
            ->visible(fn (Periodo $record): bool => ! $record->esta_cerrado)
            ->requiresConfirmation()
            ->modalHeading(fn (Periodo $record): string => "Cerrar el período {$record->etiqueta}")
            ->modalDescription('Después del cierre no se pueden registrar ni corregir lecturas de este mes, y no hay forma de reabrirlo. Confirme que todas las lecturas del ciclo ya están tomadas.')
            ->modalSubmitActionLabel('Sí, cerrar el período')
            ->action(function (Periodo $record): void {
                $record->cerrar(auth()->user());

                Notification::make()
                    ->success()
                    ->title("Período {$record->etiqueta} cerrado")
                    ->body('Ya no admite lecturas nuevas.')
                    ->send();
            });
    }
}
