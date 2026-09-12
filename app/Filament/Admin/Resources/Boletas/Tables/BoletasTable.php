<?php

namespace App\Filament\Admin\Resources\Boletas\Tables;

use App\Filament\Admin\Support\AccionesBoleta;
use App\Filament\Admin\Support\AccionesPago;
use App\Models\Boleta;
use App\Models\Periodo;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BoletasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('folio')
                    ->label('Folio')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Folio copiado'),

                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->description(fn (Boleta $record): ?string => $record->lectura?->contador?->codigo)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('periodo.etiqueta')
                    ->label('Período')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('consumo_m3')
                    ->label('Consumo')
                    ->numeric(2)
                    ->suffix(' m³')
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('monto_base')
                    ->label('Canon')
                    ->money('GTQ')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('monto_excedente')
                    ->label('Exceso')
                    ->money('GTQ')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('monto')
                    ->label('Total')
                    ->money('GTQ')
                    ->alignEnd()
                    ->weight('medium')
                    ->sortable(),

                // El saldo no es columna: sale de los pagos vigentes, así que
                // se calcula por fila y no se puede ordenar en SQL.
                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->money('GTQ')
                    ->alignEnd(),

                TextColumn::make('fecha_vencimiento')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'pagada' => 'success',
                        'vencida' => 'danger',
                        'anulada' => 'gray',
                        default => 'warning',
                    }),

                TextColumn::make('impresa_en')
                    ->label('Impresa')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sin imprimir')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['cliente', 'periodo', 'lectura.contador']))
            ->defaultSort('fecha_emision', 'desc')
            ->filters([
                SelectFilter::make('periodo_id')
                    ->label('Período')
                    ->options(fn (): array => Periodo::query()
                        ->orderByDesc('anio')
                        ->orderByDesc('mes')
                        ->get()
                        ->pluck('etiqueta', 'id')
                        ->all()),

                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'vencida' => 'Vencida',
                        'pagada' => 'Pagada',
                        'anulada' => 'Anulada',
                    ])
                    // El estado se deriva de los pagos y de la fecha, así que
                    // el filtro tiene que reproducir esa lógica con los scopes
                    // del modelo en vez de comparar una columna.
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'pendiente' => $query->pendientes()->whereDate('fecha_vencimiento', '>=', now()),
                        'vencida' => $query->vencidas(),
                        'pagada' => $query->pagadas(),
                        'anulada' => $query->anuladas(),
                        default => $query,
                    }),

                SelectFilter::make('sector')
                    ->label('Sector')
                    ->relationship('lectura.contador.predio.sector', 'nombre')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                AccionesPago::cobrar(),
                AccionesBoleta::imprimir(),
                AccionesBoleta::anular(),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Todavía no hay boletas emitidas')
            ->emptyStateDescription('Registre las lecturas del período y emítalas desde la pantalla de Lecturas.');
    }
}
