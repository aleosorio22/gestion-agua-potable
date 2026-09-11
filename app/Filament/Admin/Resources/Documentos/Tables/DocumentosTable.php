<?php

namespace App\Filament\Admin\Resources\Documentos\Tables;

use App\Filament\Admin\Support\AccionesDocumento;
use App\Models\Documento;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentosTable
{
    public static function configure(Table $table, bool $conCliente = true): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipoDocumento.nombre')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('cliente.nombre')
                    ->label('Titular')
                    ->description(fn (Documento $record): ?string => $record->cliente?->codigo)
                    ->visible($conCliente)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('predio.direccion_completa')
                    ->label('Propiedad que respalda')
                    ->placeholder('Documento de la persona')
                    ->limit(35),

                TextColumn::make('nombre_original')
                    ->label('Archivo')
                    ->limit(30)
                    ->searchable()
                    ->description(fn (Documento $record): string => number_format($record->tamano_bytes / 1024, 0).' KB'),

                IconColumn::make('firmado')
                    ->label('Firmado')
                    ->boolean(),

                TextColumn::make('fecha_firma')
                    ->label('Fecha de firma')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('subidoPor.name')
                    ->label('Cargado por')
                    ->description(fn (Documento $record): string => $record->created_at->format('d/m/Y H:i'))
                    ->toggleable(),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['tipoDocumento', 'cliente', 'predio']))
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('tipo_documento_id')
                    ->label('Tipo')
                    ->relationship('tipoDocumento', 'nombre')
                    ->preload(),

                TernaryFilter::make('respalda_propiedad')
                    ->label('Respalda')
                    ->placeholder('Todo')
                    ->trueLabel('Una propiedad')
                    ->falseLabel('A la persona')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('predio_id'),
                        false: fn (Builder $query): Builder => $query->whereNull('predio_id'),
                        blank: fn (Builder $query): Builder => $query,
                    ),

                TernaryFilter::make('firmado')
                    ->label('Firmado'),
            ])
            ->recordActions([
                AccionesDocumento::descargar(),
                AccionesDocumento::verificarIntegridad(),
                EditAction::make(),
                AccionesDocumento::eliminar(),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('No hay documentos en el expediente')
            ->emptyStateDescription('Cargue el DPI, la escritura o el contrato que respalda el servicio.');
    }
}
