<?php

namespace App\Filament\Admin\Resources\TiposDocumento\Tables;

use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TiposDocumentoTable
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

                IconColumn::make('respalda_predio')
                    ->label('Respalda predio')
                    ->boolean(),

                TextColumn::make('documentos_count')
                    ->label('Archivados')
                    ->counts('documentos'),

                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('nombre')
            ->filters([
                TernaryFilter::make('activo')
                    ->label('Activo'),

                TernaryFilter::make('respalda_predio')
                    ->label('Respalda predio')
                    ->trueLabel('Prueban propiedad')
                    ->falseLabel('Identifican a la persona'),
            ])
            ->recordActions([
                EditAction::make(),
                AccionesCatalogo::eliminar(),
            ]);
    }
}
