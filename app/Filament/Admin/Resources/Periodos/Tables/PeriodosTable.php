<?php

namespace App\Filament\Admin\Resources\Periodos\Tables;

use App\Filament\Admin\Support\AccionCerrarPeriodo;
use App\Filament\Admin\Support\AccionesCatalogo;
use App\Filament\Admin\Support\MesesDelAnio;
use App\Models\Periodo;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PeriodosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('etiqueta')
                    ->label('Período')
                    ->description(fn (Periodo $record): string => MesesDelAnio::nombre($record->mes)." de {$record->anio}")
                    ->searchable(['anio', 'mes']),

                TextColumn::make('fecha_inicio')
                    ->label('Desde')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('fecha_fin')
                    ->label('Hasta')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('lecturas_count')
                    ->label('Lecturas')
                    ->counts('lecturas')
                    ->sortable(),

                TextColumn::make('boletas_count')
                    ->label('Boletas')
                    ->counts('boletas')
                    ->sortable(),

                TextColumn::make('cerrado_en')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === null ? 'Abierto' : 'Cerrado')
                    ->color(fn (?string $state): string => $state === null ? 'success' : 'gray'),

                TextColumn::make('cerradoPor.name')
                    ->label('Cerrado por')
                    ->placeholder('—')
                    ->description(fn (Periodo $record): ?string => $record->cerrado_en?->format('d/m/Y H:i'))
                    ->toggleable(),
            ])
            ->defaultSort('fecha_inicio', 'desc')
            ->filters([
                TernaryFilter::make('abierto')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Abiertos')
                    ->falseLabel('Cerrados')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('cerrado_en'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('cerrado_en'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                AccionCerrarPeriodo::make(),
                EditAction::make(),
                // Un período cerrado no se borra aunque haya quedado vacío:
                // el cierre es el hecho que se está registrando.
                AccionesCatalogo::eliminar()
                    ->hidden(fn (Periodo $record): bool => $record->esta_cerrado),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Todavía no hay períodos')
            ->emptyStateDescription('Abra el ciclo del mes para que el lector pueda registrar lecturas.');
    }
}
