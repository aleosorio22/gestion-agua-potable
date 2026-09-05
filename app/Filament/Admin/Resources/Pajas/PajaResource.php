<?php

namespace App\Filament\Admin\Resources\Pajas;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\Pajas\Pages\CreatePaja;
use App\Filament\Admin\Resources\Pajas\Pages\EditPaja;
use App\Filament\Admin\Resources\Pajas\Pages\ListPajas;
use App\Filament\Admin\Resources\Pajas\Schemas\PajaForm;
use App\Filament\Admin\Resources\Pajas\Tables\PajasTable;
use App\Models\Paja;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PajaResource extends Resource
{
    protected static ?string $model = Paja::class;

    protected static ?string $slug = 'pajas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Catalogos;

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'paja';

    protected static ?string $pluralModelLabel = 'pajas';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return PajaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PajasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPajas::route('/'),
            'create' => CreatePaja::route('/create'),
            'edit' => EditPaja::route('/{record}/edit'),
        ];
    }
}
