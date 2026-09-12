<?php

namespace App\Filament\Admin\Resources\Predios\Tables;

use App\Filament\Admin\Support\AccionesCatalogo;
use App\Models\Predio;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrediosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // `direccion_completa` es un accesor, no una columna: la
                // búsqueda tiene que ir contra las piezas que sí están en la
                // base, o el buscador no encuentra nada.
                TextColumn::make('direccion_completa')
                    ->label('Dirección')
                    ->placeholder('Sin dirección registrada')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            $query->where('calle', 'like', "%{$search}%")
                                ->orWhere('aldea', 'like', "%{$search}%")
                                ->orWhere('numero_casa', 'like', "%{$search}%")
                                ->orWhere('zona', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('sector.nombre')
                    ->label('Sector')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Sin sector')
                    ->sortable(),

                // El predio no guarda titular: sale de los contadores que
                // tiene instalados. Un predio sin ninguno es una dirección
                // registrada a la que todavía no le llega el servicio.
                TextColumn::make('clientes.nombre')
                    ->label('Titular')
                    ->placeholder('Sin contador instalado')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList(),

                TextColumn::make('referencia')
                    ->label('Referencia')
                    ->limit(40)
                    ->placeholder('Sin referencia')
                    ->searchable()
                    ->toggleable(),

                // Delata las propiedades conectadas sin nada que respalde el
                // derecho del titular sobre ellas, que es lo que el expediente
                // existe para evitar.
                IconColumn::make('respaldo_documental')
                    ->label('Respaldo')
                    ->state(fn (Predio $record): bool => $record->documentos_count > 0)
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedDocumentCheck)
                    ->falseIcon(Heroicon::OutlinedExclamationTriangle)
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn (Predio $record): string => $record->documentos_count > 0
                        ? 'Tiene documento que la respalda'
                        : 'Sin escritura, recibo de luz ni contrato cargado'),

                TextColumn::make('contadores_count')
                    ->label('Contadores')
                    ->counts('contadores')
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
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('clientes')->withCount('documentos'))
            ->defaultSort('aldea')
            ->filters([
                SelectFilter::make('sector_id')
                    ->label('Sector')
                    ->relationship('sector', 'nombre')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('cliente')
                    ->label('Titular')
                    ->relationship('clientes', 'nombre')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('respaldo_documental')
                    ->label('Respaldo documental')
                    ->placeholder('Todos')
                    ->trueLabel('Con documento')
                    ->falseLabel('Sin documento')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->has('documentos'),
                        false: fn (Builder $query): Builder => $query->doesntHave('documentos'),
                        blank: fn (Builder $query): Builder => $query,
                    ),

                TernaryFilter::make('sin_contador')
                    ->label('Contador instalado')
                    ->placeholder('Todos')
                    ->trueLabel('Con contador')
                    ->falseLabel('Sin contador')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->has('contadores'),
                        false: fn (Builder $query): Builder => $query->doesntHave('contadores'),
                        blank: fn (Builder $query): Builder => $query,
                    ),

                TrashedFilter::make()
                    ->label('Eliminados'),
            ])
            ->recordActions([
                EditAction::make(),
                AccionesCatalogo::eliminar(),
                RestoreAction::make(),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Todavía no hay predios')
            ->emptyStateDescription('Registre la propiedad donde se instalará el contador.');
    }
}
