<?php

namespace App\Filament\Admin\Support;

use App\Models\Boleta;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Services\RegistradorPagos;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use RuntimeException;

/**
 * El cobro en ventanilla y su reverso.
 *
 * `RegistradorPagos` protege sus reglas lanzando RuntimeException —boleta
 * anulada, monto mayor que el saldo, método que exige referencia, sin serie de
 * recibos—. Aquí se atrapan y se muestran como notificación: quien cobra tiene
 * que leer qué pasó, no toparse con una pantalla de error con el vecino
 * enfrente.
 */
class AccionesPago
{
    public static function cobrar(): Action
    {
        return Action::make('cobrar')
            ->label('Registrar pago')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            // Una boleta anulada o ya saldada no se cobra.
            ->visible(fn (Boleta $record): bool => ! $record->esta_anulada && $record->saldo > 0)
            ->modalHeading(fn (Boleta $record): string => "Cobrar la boleta {$record->folio}")
            ->modalSubmitActionLabel('Registrar el cobro')
            ->fillForm(fn (Boleta $record): array => [
                'monto' => $record->saldo,
                'fecha_pago' => now()->toDateString(),
            ])
            ->schema([
                Placeholder::make('resumen')
                    ->label('A cobrar')
                    ->content(fn (Boleta $record): string => "{$record->cliente->nombre} — saldo pendiente Q"
                        .number_format($record->saldo, 2)
                        .' de un total de Q'.number_format((float) $record->monto, 2)),

                Select::make('metodo_pago_id')
                    ->label('Método de pago')
                    ->required()
                    ->native(false)
                    ->live()
                    ->options(fn (): array => MetodoPago::query()
                        ->where('activo', true)
                        ->orderBy('nombre')
                        ->pluck('nombre', 'id')
                        ->all()),

                TextInput::make('referencia')
                    ->label('Número de referencia')
                    // El depósito y el cheque necesitan con qué rastrearse; el
                    // efectivo no tiene nada que anotar.
                    ->required(fn (Get $get): bool => static::exigeReferencia($get('metodo_pago_id')))
                    ->visible(fn (Get $get): bool => static::exigeReferencia($get('metodo_pago_id')))
                    ->maxLength(100)
                    ->validationMessages([
                        'required' => 'Ese método de pago necesita el número con el que se puede rastrear.',
                    ])
                    ->helperText('Número de la boleta de depósito, del cheque o de la transferencia.'),

                TextInput::make('monto')
                    ->label('Monto recibido')
                    ->required()
                    ->numeric()
                    ->prefix('Q')
                    ->step(0.01)
                    ->minValue(0.01)
                    // Se permite menos que el saldo: un abono parcial es una
                    // situación normal en ventanilla.
                    ->maxValue(fn (Boleta $record): float => $record->saldo)
                    ->validationMessages([
                        'max' => 'El monto no puede pasar del saldo pendiente de la boleta.',
                        'min' => 'El monto debe ser mayor que cero.',
                    ])
                    ->helperText('Viene con el saldo completo. Cámbielo si el vecino abona una parte.'),

                DatePicker::make('fecha_pago')
                    ->label('Fecha del pago')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->maxDate(now())
                    ->validationMessages([
                        'before_or_equal' => 'La fecha del pago no puede ser futura.',
                    ]),
            ])
            ->action(function (Boleta $record, array $data): void {
                try {
                    $pago = app(RegistradorPagos::class)->registrar(
                        boleta: $record,
                        metodoPago: MetodoPago::findOrFail($data['metodo_pago_id']),
                        usuario: auth()->user(),
                        monto: (float) $data['monto'],
                        referencia: $data['referencia'] ?? null,
                        fechaPago: $data['fecha_pago'],
                    );
                } catch (RuntimeException $error) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo registrar el pago')
                        ->body($error->getMessage())
                        ->persistent()
                        ->send();

                    return;
                }

                $saldo = $record->fresh()->saldo;

                Notification::make()
                    ->success()
                    ->title("Recibo {$pago->folio} emitido")
                    ->body($saldo > 0
                        ? 'Queda un saldo de Q'.number_format($saldo, 2).' en esta boleta.'
                        : 'La boleta quedó saldada.')
                    ->send();
            });
    }

    public static function revertir(): Action
    {
        return Action::make('revertir')
            ->label('Revertir')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('danger')
            ->visible(fn (Pago $record): bool => ! $record->esta_revertido)
            ->requiresConfirmation()
            ->modalHeading(fn (Pago $record): string => "Revertir el recibo {$record->folio}")
            ->modalDescription('El pago no se borra: queda registrado junto con el hecho que lo anula, con quién lo revirtió y por qué. El saldo de la boleta vuelve a subir.')
            ->modalSubmitActionLabel('Revertir el pago')
            ->schema([
                Textarea::make('motivo')
                    ->label('Motivo del reverso')
                    ->required()
                    ->maxLength(255)
                    ->rows(2)
                    ->helperText('Ej.: cheque rechazado, monto mal digitado, cobro duplicado.'),
            ])
            ->action(function (Pago $record, array $data): void {
                $record->revertir(auth()->user(), $data['motivo']);

                Notification::make()
                    ->success()
                    ->title("Recibo {$record->folio} revertido")
                    ->body('El saldo volvió a la boleta '.$record->boleta->folio.'.')
                    ->send();
            });
    }

    public static function imprimirRecibo(): Action
    {
        return Action::make('imprimirRecibo')
            ->label('Imprimir recibo')
            ->icon(Heroicon::OutlinedPrinter)
            ->color('gray')
            ->visible(fn (Pago $record): bool => ! $record->esta_revertido)
            ->url(fn (Pago $record): string => route('recibos.pago', $record), shouldOpenInNewTab: true);
    }

    private static function exigeReferencia(mixed $metodoId): bool
    {
        return $metodoId
            ? (bool) MetodoPago::whereKey($metodoId)->value('requiere_referencia')
            : false;
    }
}
