<?php

namespace App\Filament\Admin\Resources\MetodosPago\Tables;

use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MetodosPagoTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('requiere_referencia')
                    ->label('Pide referencia')
                    ->boolean(),

                TextColumn::make('pagos_count')
                    ->label('Pagos')
                    ->counts('pagos'),

                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('nombre')
            ->filters([
                TernaryFilter::make('activo')
                    ->label('Activo'),

                TernaryFilter::make('requiere_referencia')
                    ->label('Pide referencia'),
            ])
            ->recordActions([
                EditAction::make(),
                AccionesCatalogo::eliminar(),
            ]);
    }
}
