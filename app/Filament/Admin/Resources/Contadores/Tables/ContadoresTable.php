<?php

namespace App\Filament\Admin\Resources\Contadores\Tables;

use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ContadoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Código copiado'),

                TextColumn::make('cliente.nombre')
                    ->label('Titular')
                    ->description(fn ($record): ?string => $record->cliente?->codigo)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('predio.direccion_completa')
                    ->label('Dirección del servicio')
                    ->placeholder('Sin dirección registrada')
                    ->limit(40),

                TextColumn::make('predio.sector.nombre')
                    ->label('Sector')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Sin sector')
                    ->toggleable(),

                TextColumn::make('paja.nombre')
                    ->label('Paja')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('lecturas_count')
                    ->label('Lecturas')
                    ->counts('lecturas')
                    ->sortable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'activo' => 'success',
                        'dañado' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('fecha_instalacion')
                    ->label('Instalado')
                    ->date('d/m/Y')
                    ->placeholder('Sin fecha')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('codigo')
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'inactivo' => 'Inactivo',
                        'dañado' => 'Dañado',
                    ]),

                SelectFilter::make('paja_id')
                    ->label('Paja')
                    ->relationship('paja', 'nombre')
                    ->preload(),

                SelectFilter::make('sector')
                    ->label('Sector')
                    ->relationship('predio.sector', 'nombre')
                    ->searchable()
                    ->preload(),

                TrashedFilter::make()
                    ->label('Eliminados'),
            ])
            ->recordActions([
                EditAction::make(),
                AccionesCatalogo::eliminar(),
                RestoreAction::make(),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Todavía no hay contadores')
            ->emptyStateDescription('Instale un medidor en un predio y asígneselo a su titular.');
    }
}
