<?php

namespace App\Filament\Admin\Resources\Contadores;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\Contadores\Pages\CreateContador;
use App\Filament\Admin\Resources\Contadores\Pages\EditContador;
use App\Filament\Admin\Resources\Contadores\Pages\ListContadores;
use App\Filament\Admin\Resources\Contadores\Schemas\ContadorForm;
use App\Filament\Admin\Resources\Contadores\Tables\ContadoresTable;
use App\Models\Contador;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ContadorResource extends Resource
{
    protected static ?string $model = Contador::class;

    protected static ?string $slug = 'contadores';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Padron;

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'contador';

    protected static ?string $pluralModelLabel = 'contadores';

    protected static ?string $recordTitleAttribute = 'codigo';

    public static function form(Schema $schema): Schema
    {
        return ContadorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContadoresTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContadores::route('/'),
            'create' => CreateContador::route('/create'),
            'edit' => EditContador::route('/{record}/edit'),
        ];
    }

    /**
     * En campo el lector llega con el código grabado en el aparato y sin saber
     * de quién es: la búsqueda global por código le devuelve el titular.
     *
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['codigo'];
    }

    /**
     * @return array<string, string|null>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Titular' => $record->cliente?->nombre,
            'Dirección' => $record->predio?->direccion_completa,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['cliente', 'predio']);
    }

    /**
     * Un contador eliminado tiene que poder abrirse para restaurarlo: su
     * código no se reutiliza, así que reponerlo es restaurar este registro.
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
