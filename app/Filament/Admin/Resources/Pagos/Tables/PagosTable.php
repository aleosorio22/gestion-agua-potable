<?php

namespace App\Filament\Admin\Resources\Pagos\Tables;

use App\Filament\Admin\Support\AccionesPago;
use App\Models\MetodoPago;
use App\Models\Pago;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class PagosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('folio')
                    ->label('Recibo')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Folio copiado'),

                TextColumn::make('boleta.cliente.nombre')
                    ->label('Cliente')
                    ->description(fn (Pago $record): ?string => 'Boleta '.$record->boleta?->folio)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('metodoPago.nombre')
                    ->label('Método')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('referencia')
                    ->label('Referencia')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('monto')
                    ->label('Monto')
                    ->money('GTQ')
                    ->alignEnd()
                    ->weight('medium')
                    ->sortable()
                    // Solo cuenta lo vigente: un reverso no entra en la caja.
                    // El sumarizador corre sobre el query builder crudo, no
                    // sobre el de Eloquent: los scopes del modelo no existen acá.
                    ->summarize(
                        Sum::make()
                            ->label('Total cobrado')
                            ->money('GTQ')
                            ->query(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('revertido_en'))
                    ),

                TextColumn::make('fecha_pago')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('usuario.name')
                    ->label('Cobró')
                    ->toggleable(),

                TextColumn::make('revertido_en')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Pago $record): string => $record->esta_revertido ? 'Revertido' : 'Vigente')
                    ->color(fn (Pago $record): string => $record->esta_revertido ? 'danger' : 'success')
                    ->description(fn (Pago $record): ?string => $record->motivo_reverso),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['boleta.cliente', 'metodoPago', 'usuario']))
            ->defaultSort('fecha_pago', 'desc')
            ->filters([
                SelectFilter::make('metodo_pago_id')
                    ->label('Método')
                    ->options(fn (): array => MetodoPago::query()->orderBy('nombre')->pluck('nombre', 'id')->all()),

                TernaryFilter::make('vigente')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Vigentes')
                    ->falseLabel('Revertidos')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->vigentes(),
                        false: fn (Builder $query): Builder => $query->revertidos(),
                        blank: fn (Builder $query): Builder => $query,
                    ),

                // El corte de caja del día es la consulta con la que cierra la
                // ventanilla; por eso `fecha_pago` tiene índice propio.
                Filter::make('fecha')
                    ->schema([
                        DatePicker::make('desde')
                            ->label('Cobrado desde')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('hasta')
                            ->label('Cobrado hasta')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, $desde) => $q->whereDate('fecha_pago', '>=', $desde))
                        ->when($data['hasta'] ?? null, fn (Builder $q, $hasta) => $q->whereDate('fecha_pago', '<=', $hasta))),
            ])
            ->recordActions([
                AccionesPago::imprimirRecibo(),
                AccionesPago::revertir(),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Todavía no hay pagos registrados')
            ->emptyStateDescription('Los cobros se registran desde la boleta del vecino.');
    }
}
