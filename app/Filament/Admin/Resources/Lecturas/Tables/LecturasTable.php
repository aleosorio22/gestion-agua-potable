<?php

namespace App\Filament\Admin\Resources\Lecturas\Tables;

use App\Models\Lectura;
use App\Models\Periodo;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LecturasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contador.codigo')
                    ->label('Contador')
                    ->description(fn (Lectura $record): ?string => $record->contador?->cliente?->nombre)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('periodo.etiqueta')
                    ->label('Período')
                    ->badge()
                    // Sin sortable: `etiqueta` es un accesor del período y
                    // ordenar por él exigiría unir la tabla a mano.
                    ->color('gray'),

                TextColumn::make('fecha_lectura')
                    ->label('Visita')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('lectura_anterior')
                    ->label('Anterior')
                    ->numeric(2)
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('lectura_actual')
                    ->label('Actual')
                    ->numeric(2)
                    ->alignEnd(),

                TextColumn::make('consumo_m3')
                    ->label('Consumo')
                    ->numeric(2)
                    ->suffix(' m³')
                    ->alignEnd()
                    ->weight('medium')
                    ->sortable(),

                TextColumn::make('boleta.folio')
                    ->label('Boleta')
                    ->placeholder('Sin facturar')
                    ->badge()
                    ->color(fn (?string $state): string => $state === null ? 'warning' : 'success'),

                TextColumn::make('usuario.name')
                    ->label('Lector')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(30)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['contador.cliente', 'periodo', 'boleta']))
            ->defaultSort('fecha_lectura', 'desc')
            ->filters([
                SelectFilter::make('periodo_id')
                    ->label('Período')
                    ->options(fn (): array => Periodo::query()
                        ->orderByDesc('anio')
                        ->orderByDesc('mes')
                        ->get()
                        ->pluck('etiqueta', 'id')
                        ->all())
                    // Arranca en el período abierto: es sobre el que se trabaja
                    // todos los días, y verlo mezclado con el histórico no ayuda.
                    ->default(fn (): ?int => Periodo::abiertos()->orderByDesc('fecha_inicio')->value('id')),

                SelectFilter::make('sector')
                    ->label('Sector')
                    ->relationship('contador.predio.sector', 'nombre')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('facturada')
                    ->label('Facturación')
                    ->placeholder('Todas')
                    ->trueLabel('Ya facturadas')
                    ->falseLabel('Pendientes de facturar')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->has('boleta'),
                        false: fn (Builder $query): Builder => $query->doesntHave('boleta'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->disabled(fn (Lectura $record): bool => $record->esta_facturada)
                    ->tooltip(fn (Lectura $record): ?string => $record->esta_facturada
                        ? 'Ya tiene boleta emitida. Anule la boleta para poder corregirla.'
                        : null),

                // Una lectura facturada no se borra: el observer lo impide, y
                // aquí el botón se apaga para que no haya que descubrirlo
                // chocando contra una excepción.
                DeleteAction::make()
                    ->disabled(fn (Lectura $record): bool => $record->esta_facturada)
                    ->tooltip(fn (Lectura $record): ?string => $record->esta_facturada
                        ? 'Ya tiene boleta emitida. Anule la boleta primero.'
                        : null)
                    ->modalDescription(fn (Model $record): string => "Se borrará la lectura del contador {$record->contador->codigo}. El contador volverá a aparecer como pendiente en este período."),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Todavía no hay lecturas en este período')
            ->emptyStateDescription('Registre la primera visita para que se pueda emitir la boleta.');
    }
}
