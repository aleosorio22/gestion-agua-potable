<?php

namespace App\Filament\Admin\Resources\SeriesDocumento\Tables;

use App\Filament\Admin\Support\AccionesCatalogo;
use App\Models\SerieDocumento;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SeriesDocumentoTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipo_documento')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'boleta' => 'Boleta de cobro',
                        'recibo_pago' => 'Recibo de pago',
                        default => $state,
                    })
                    ->color(fn (string $state): string => $state === 'boleta' ? 'warning' : 'info'),

                TextColumn::make('codigo')
                    ->label('Serie')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('proximo_folio')
                    ->label('Próximo folio')
                    ->state(fn (SerieDocumento $record): string => $record->formatearFolio(
                        (int) $record->siguiente_numero,
                        (int) $record->ejercicio,
                    ))
                    ->badge()
                    ->color('gray')
                    ->copyable(),

                TextColumn::make('ejercicio')
                    ->label('Ejercicio')
                    ->sortable(),

                IconColumn::make('reinicia_cada_anio')
                    ->label('Reinicia por año')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('boletas_count')
                    ->label('Boletas')
                    ->counts('boletas'),

                TextColumn::make('pagos_count')
                    ->label('Recibos')
                    ->counts('pagos'),

                IconColumn::make('activa')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->defaultSort('tipo_documento')
            ->filters([
                SelectFilter::make('tipo_documento')
                    ->label('Tipo')
                    ->options([
                        'boleta' => 'Boleta de cobro',
                        'recibo_pago' => 'Recibo de pago',
                    ]),

                TernaryFilter::make('activa')
                    ->label('Activa'),
            ])
            ->recordActions([
                EditAction::make(),
                AccionesCatalogo::eliminar(),
            ]);
    }
}
