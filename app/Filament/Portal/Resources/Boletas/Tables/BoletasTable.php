<?php

namespace App\Filament\Portal\Resources\Boletas\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BoletasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('folio')
                    ->label('Boleta')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('fecha_emision')
                    ->label('Emitida')
                    ->date()
                    ->sortable(),

                TextColumn::make('consumo_m3')
                    ->label('Consumo')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' m³'),

                TextColumn::make('monto')
                    ->label('Monto')
                    ->money('GTQ')
                    ->sortable(),

                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->money('GTQ'),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pagada' => 'success',
                        'pendiente' => 'warning',
                        'vencida' => 'danger',
                        'anulada' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('fecha_vencimiento')
                    ->label('Vence')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('fecha_emision', 'desc')
            // De solo lectura a propósito: sin EditAction, sin borrado.
            ->recordActions([]);
    }
}
