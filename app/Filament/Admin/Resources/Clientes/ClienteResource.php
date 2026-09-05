<?php

namespace App\Filament\Admin\Resources\Clientes;

use App\Filament\Admin\Resources\Clientes\Pages\CreateCliente;
use App\Filament\Admin\Resources\Clientes\Pages\EditCliente;
use App\Filament\Admin\Resources\Clientes\Pages\ListClientes;
use App\Filament\Admin\Resources\Clientes\Schemas\ClienteForm;
use App\Filament\Admin\Resources\Clientes\Tables\ClientesTable;
use App\Models\Cliente;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Clientes';

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
            //
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

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
