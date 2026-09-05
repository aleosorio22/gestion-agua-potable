<?php

namespace App\Filament\Admin\Resources\Pajas\Tables;

use App\Filament\Admin\Support\AccionesCatalogo;
use App\Models\Paja;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PajasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('equivalencia_m3')
                    ->label('Equivalencia')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' m³')
                    ->sortable(),

                // Una consulta por fila, pero el catálogo tiene un puñado de
                // filas y saber el precio de hoy es justo lo que se viene a ver.
                TextColumn::make('tarifa_vigente')
                    ->label('Tarifa vigente')
                    ->state(fn (Paja $record): ?string => $record->tarifaVigenteEn()?->monto_base)
                    ->money('GTQ')
                    ->placeholder('Sin tarifa'),

                TextColumn::make('tarifas_count')
                    ->label('Tarifas')
                    ->counts('tarifas'),

                TextColumn::make('contadores_count')
                    ->label('Contadores')
                    ->counts('contadores'),

                IconColumn::make('activo')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->defaultSort('nombre')
            ->filters([
                TernaryFilter::make('activo')
                    ->label('Activa'),
            ])
            ->recordActions([
                EditAction::make(),
                AccionesCatalogo::eliminar(),
            ]);
    }
}
