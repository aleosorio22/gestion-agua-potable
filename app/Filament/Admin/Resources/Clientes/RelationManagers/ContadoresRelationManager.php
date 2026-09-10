<?php

namespace App\Filament\Admin\Resources\Clientes\RelationManagers;

use App\Filament\Admin\Resources\Contadores\Schemas\ContadorForm;
use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Los servicios de agua del cliente, dentro de su propia ficha.
 *
 * Un cliente puede tener varios contadores, en la misma propiedad o en otra:
 * verlos juntos es lo que responde en ventanilla «¿cuántos servicios tiene
 * esta persona y dónde están?» sin ir a buscarlos al listado general.
 */
class ContadoresRelationManager extends RelationManager
{
    protected static string $relationship = 'contadores';

    protected static ?string $title = 'Contadores del cliente';

    protected static ?string $modelLabel = 'contador';

    protected static ?string $pluralModelLabel = 'contadores';

    public function form(Schema $schema): Schema
    {
        return ContadorForm::configure($schema, conTitular: false);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('codigo')
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Código copiado'),

                TextColumn::make('predio.direccion_completa')
                    ->label('Dirección del servicio')
                    ->placeholder('Sin dirección registrada')
                    ->limit(40),

                TextColumn::make('paja.nombre')
                    ->label('Paja')
                    ->badge()
                    ->color('info'),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'activo' => 'success',
                        'dañado' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar contador'),
            ])
            ->recordActions([
                EditAction::make(),
                AccionesCatalogo::eliminar(),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Este cliente no tiene contadores')
            ->emptyStateDescription('Agréguele uno para que entre en la ruta de lectura.');
    }
}
