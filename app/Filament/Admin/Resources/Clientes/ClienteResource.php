<?php

namespace App\Filament\Admin\Resources\Clientes;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\Clientes\Pages\CreateCliente;
use App\Filament\Admin\Resources\Clientes\Pages\EditCliente;
use App\Filament\Admin\Resources\Clientes\Pages\ListClientes;
use App\Filament\Admin\Resources\Clientes\RelationManagers\AccesosPortalRelationManager;
use App\Filament\Admin\Resources\Clientes\RelationManagers\ContadoresRelationManager;
use App\Filament\Admin\Resources\Clientes\Schemas\ClienteForm;
use App\Filament\Admin\Resources\Clientes\Tables\ClientesTable;
use App\Models\Cliente;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $slug = 'clientes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Padron;

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return ClienteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ContadoresRelationManager::class,
            AccesosPortalRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientes::route('/'),
            'create' => CreateCliente::route('/create'),
            'edit' => EditCliente::route('/{record}/edit'),
        ];
    }

    /**
     * En ventanilla se busca por lo que el vecino trae a mano: el código de su
     * recibo o el número de su documento, no siempre el nombre.
     *
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['codigo', 'nombre', 'nit', 'dpi'];
    }

    /**
     * @return array<string, string|null>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Código' => $record->codigo,
            'DPI' => $record->dpi,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Cliente::activos()->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Clientes activos';
    }

    /**
     * Un cliente eliminado tiene que poder abrirse para restaurarlo; sin esto
     * la URL de su edición contesta 404 y la papelera es un callejón sin salida.
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
