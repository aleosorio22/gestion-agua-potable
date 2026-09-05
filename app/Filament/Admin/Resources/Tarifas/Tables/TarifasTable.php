<?php

namespace App\Filament\Admin\Resources\Tarifas\Tables;

use App\Filament\Admin\Support\AccionesCatalogo;
use App\Models\Tarifa;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TarifasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paja.nombre')
                    ->label('Paja')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('monto_base')
                    ->label('Cuota fija')
                    ->money('GTQ')
                    ->sortable(),

                TextColumn::make('precio_m3_excedente')
                    ->label('Excedente por m³')
                    ->money('GTQ', decimalPlaces: 4)
                    ->sortable(),

                TextColumn::make('vigente_desde')
                    ->label('Desde')
                    ->date('d/m/Y')
                    ->sortable(),

                // Derivado del inicio de la siguiente tarifa, no almacenado.
                TextColumn::make('vigente_hasta')
                    ->label('Hasta')
                    ->date('d/m/Y')
                    ->placeholder('Vigente'),

                TextColumn::make('es_vigente')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Vigente' : 'Histórica')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                TextColumn::make('boletas_count')
                    ->label('Boletas')
                    ->counts('boletas')
                    ->description('emitidas con esta tarifa'),
            ])
            ->defaultSort('vigente_desde', 'desc')
            ->filters([
                SelectFilter::make('paja')
                    ->label('Paja')
                    ->relationship('paja', 'nombre')
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),

                // Una tarifa que ya cobró es parte del expediente. Para cambiar
                // el precio se registra una nueva con otra fecha de vigencia.
                EditAction::make()
                    ->disabled(fn (Tarifa $record): bool => $record->estaEnUso())
                    ->tooltip(fn (Tarifa $record): ?string => $record->estaEnUso()
                        ? 'Ya se emitieron boletas con esta tarifa. Registre una nueva con otra fecha de vigencia.'
                        : null),

                AccionesCatalogo::eliminar(),
            ]);
    }
}
