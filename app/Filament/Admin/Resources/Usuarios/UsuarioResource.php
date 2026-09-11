<?php

namespace App\Filament\Admin\Resources\Usuarios;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\Usuarios\Pages\CreateUsuario;
use App\Filament\Admin\Resources\Usuarios\Pages\EditUsuario;
use App\Filament\Admin\Resources\Usuarios\Pages\ListUsuarios;
use App\Filament\Admin\Resources\Usuarios\Schemas\UsuarioForm;
use App\Filament\Admin\Resources\Usuarios\Tables\UsuariosTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UsuarioResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'usuarios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Administracion;

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return UsuarioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsuariosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsuarios::route('/'),
            'create' => CreateUsuario::route('/create'),
            'edit' => EditUsuario::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) User::activos()->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Cuentas activas';
    }
}
