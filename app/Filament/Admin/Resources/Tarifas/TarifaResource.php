<?php

namespace App\Filament\Admin\Resources\Tarifas;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\Tarifas\Pages\CreateTarifa;
use App\Filament\Admin\Resources\Tarifas\Pages\EditTarifa;
use App\Filament\Admin\Resources\Tarifas\Pages\ListTarifas;
use App\Filament\Admin\Resources\Tarifas\Schemas\TarifaForm;
use App\Filament\Admin\Resources\Tarifas\Tables\TarifasTable;
use App\Models\Tarifa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TarifaResource extends Resource
{
    protected static ?string $model = Tarifa::class;

    protected static ?string $slug = 'tarifas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Catalogos;

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'tarifa';

    protected static ?string $pluralModelLabel = 'tarifas';

    public static function form(Schema $schema): Schema
    {
        return TarifaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TarifasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTarifas::route('/'),
            'create' => CreateTarifa::route('/create'),
            'edit' => EditTarifa::route('/{record}/edit'),
        ];
    }
}
