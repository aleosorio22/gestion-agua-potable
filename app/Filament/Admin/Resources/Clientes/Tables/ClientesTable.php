<?php

namespace App\Filament\Admin\Resources\Clientes\Tables;

use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ClientesTable
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

                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): ?string => $record->email),

                TextColumn::make('dpi')
                    ->label('DPI')
                    ->searchable()
                    ->placeholder('Sin DPI')
                    ->toggleable(),

                TextColumn::make('nit')
                    ->label('NIT')
                    ->searchable()
                    ->placeholder('Sin NIT')
                    ->toggleable(),

                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable()
                    ->placeholder('Sin teléfono')
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('Correo electrónico')
                    ->searchable()
                    ->placeholder('Sin correo')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('direccion_notificacion')
                    ->label('Dirección de notificación')
                    ->limit(40)
                    ->placeholder('Sin dirección')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('contadores_count')
                    ->label('Contadores')
                    ->counts('contadores')
                    ->sortable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'activo' => 'Activo',
                        'inactivo' => 'Inactivo',
                        default => $state,
                    })
                    ->color(fn (string $state): string => $state === 'activo' ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nombre')
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'inactivo' => 'Inactivo',
                    ]),

                TrashedFilter::make()
                    ->label('Eliminados'),
            ])
            ->recordActions([
                EditAction::make(),
                AccionesCatalogo::eliminar(),
                RestoreAction::make(),
            ])
            // Sin acciones masivas de borrado a propósito: la baja de un cliente
            // se decide de una en una, mirando si ya tiene contadores o boletas.
            ->toolbarActions([])
            ->emptyStateHeading('Todavía no hay clientes')
            ->emptyStateDescription('Registre al titular del servicio para poder asignarle un contador.');
    }
}
