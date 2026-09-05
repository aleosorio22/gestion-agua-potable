<?php

namespace App\Filament\Admin\Resources\Sectores\Tables;

use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SectoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('orden')
                    ->label('Orden')
                    ->sortable(),

                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(40)
                    ->toggleable(),

                TextColumn::make('predios_count')
                    ->label('Predios')
                    ->counts('predios')
                    ->sortable(),

                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('orden')
            ->filters([
                TernaryFilter::make('activo')
                    ->label('Activo'),
            ])
            ->recordActions([
                EditAction::make(),
                AccionesCatalogo::eliminar(),
            ]);
    }
}
