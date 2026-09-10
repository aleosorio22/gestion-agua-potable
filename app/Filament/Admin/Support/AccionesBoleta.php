<?php

namespace App\Filament\Admin\Support;

use App\Models\Boleta;
use App\Models\Lectura;
use App\Services\EmisorBoletas;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * Emisión, anulación e impresión de boletas, con la misma forma en todas las
 * pantallas.
 *
 * `EmisorBoletas` protege sus reglas lanzando RuntimeException —período
 * cerrado, sin tarifa vigente, sin serie activa—. Aquí se atrapan y se
 * convierten en notificación: el usuario tiene que leer qué falta, no toparse
 * con una pantalla de error.
 */
class AccionesBoleta
{
    public static function emitir(): Action
    {
        return Action::make('emitir')
            ->label('Emitir boleta')
            ->icon(Heroicon::OutlinedDocumentCurrencyDollar)
            ->color('success')
            ->visible(fn (Lectura $record): bool => ! $record->esta_facturada)
            ->requiresConfirmation()
            ->modalHeading('Emitir la boleta de esta lectura')
            ->modalDescription('Se calcula con la tarifa vigente a la fecha de la visita y se le asigna folio. Una vez emitida, sus importes ya no cambian: para corregirla hay que anularla y emitir otra.')
            ->modalSubmitActionLabel('Emitir')
            ->action(function (Lectura $record): void {
                try {
                    $boleta = app(EmisorBoletas::class)->emitir($record);
                } catch (RuntimeException $error) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo emitir la boleta')
                        ->body($error->getMessage())
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title("Boleta {$boleta->folio} emitida")
                    ->body('Total a cobrar: Q'.number_format((float) $boleta->monto, 2).'.')
                    ->send();
            });
    }

    /**
     * Cierra el mes de un golpe: emite todas las lecturas seleccionadas que
     * todavía no tengan boleta, y reporta al final cuántas quedaron fuera y
     * por qué. Una lectura que falle no detiene a las demás.
     */
    public static function emitirEnLote(): BulkAction
    {
        return BulkAction::make('emitirEnLote')
            ->label('Emitir boletas')
            ->icon(Heroicon::OutlinedDocumentCurrencyDollar)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Emitir las boletas de las lecturas seleccionadas')
            ->modalDescription('Las lecturas que ya tengan boleta se saltan. Si alguna no se puede emitir, las demás siguen.')
            ->modalSubmitActionLabel('Emitir todas')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                $emisor = app(EmisorBoletas::class);
                $emitidas = 0;
                $problemas = [];

                foreach ($records as $lectura) {
                    if ($lectura->esta_facturada) {
                        continue;
                    }

                    try {
                        $emisor->emitir($lectura);
                        $emitidas++;
                    } catch (RuntimeException $error) {
                        $problemas[] = $error->getMessage();
                    }
                }

                $notificacion = Notification::make()
                    ->title($emitidas === 1 ? '1 boleta emitida' : "{$emitidas} boletas emitidas");

                if ($problemas === []) {
                    $notificacion->success()->send();

                    return;
                }

                // Los motivos se repiten (misma paja sin tarifa, misma serie
                // sin configurar): mostrarlos una vez es más útil que listar
                // cuarenta veces lo mismo.
                $notificacion
                    ->warning()
                    ->body(implode(' ', array_unique($problemas)))
                    ->persistent()
                    ->send();
            });
    }

    public static function anular(): Action
    {
        return Action::make('anular')
            ->label('Anular')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (Boleta $record): bool => ! $record->esta_anulada)
            ->requiresConfirmation()
            ->modalHeading(fn (Boleta $record): string => "Anular la boleta {$record->folio}")
            ->modalDescription('La boleta no se borra: queda registrada como anulada, con quién la anuló y por qué. Es lo único que admite un documento ya emitido.')
            ->modalSubmitActionLabel('Anular la boleta')
            ->schema([
                Textarea::make('motivo')
                    ->label('Motivo de la anulación')
                    ->required()
                    ->maxLength(255)
                    ->rows(2)
                    ->helperText('Queda en el documento y en la auditoría. Ej.: lectura mal tomada, medidor cambiado.'),
            ])
            ->action(function (Boleta $record, array $data): void {
                $record->anular(auth()->user(), $data['motivo']);

                Notification::make()
                    ->success()
                    ->title("Boleta {$record->folio} anulada")
                    ->body('La lectura vuelve a quedar disponible para emitir una nueva.')
                    ->send();
            });
    }

    /**
     * Abre el documento de cobro del servicio: todas las boletas pendientes de
     * esa tarjeta, como el recibo que el vecino ya conoce.
     */
    public static function imprimir(): Action
    {
        return Action::make('imprimir')
            ->label('Imprimir recibo')
            ->icon(Heroicon::OutlinedPrinter)
            ->color('gray')
            ->url(fn (Boleta $record): string => route('recibos.contador', $record->lectura->contador_id), shouldOpenInNewTab: true);
    }
}
