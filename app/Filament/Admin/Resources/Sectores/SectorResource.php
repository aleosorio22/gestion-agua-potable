<?php

namespace App\Filament\Admin\Resources\Sectores;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\Sectores\Pages\CreateSector;
use App\Filament\Admin\Resources\Sectores\Pages\EditSector;
use App\Filament\Admin\Resources\Sectores\Pages\ListSectores;
use App\Filament\Admin\Resources\Sectores\Schemas\SectorForm;
use App\Filament\Admin\Resources\Sectores\Tables\SectoresTable;
use App\Models\Sector;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SectorResource extends Resource
{
    protected static ?string $model = Sector::class;

    protected static ?string $slug = 'sectores';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Catalogos;

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'sector';

    protected static ?string $pluralModelLabel = 'sectores';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return SectorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SectoresTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSectores::route('/'),
            'create' => CreateSector::route('/create'),
            'edit' => EditSector::route('/{record}/edit'),
        ];
    }
}
