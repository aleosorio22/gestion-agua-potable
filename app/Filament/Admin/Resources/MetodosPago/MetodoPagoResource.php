<?php

namespace App\Filament\Admin\Resources\MetodosPago;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\MetodosPago\Pages\CreateMetodoPago;
use App\Filament\Admin\Resources\MetodosPago\Pages\EditMetodoPago;
use App\Filament\Admin\Resources\MetodosPago\Pages\ListMetodosPago;
use App\Filament\Admin\Resources\MetodosPago\Schemas\MetodoPagoForm;
use App\Filament\Admin\Resources\MetodosPago\Tables\MetodosPagoTable;
use App\Models\MetodoPago;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MetodoPagoResource extends Resource
{
    protected static ?string $model = MetodoPago::class;

    protected static ?string $slug = 'metodos-pago';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Catalogos;

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'método de pago';

    protected static ?string $pluralModelLabel = 'métodos de pago';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return MetodoPagoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MetodosPagoTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMetodosPago::route('/'),
            'create' => CreateMetodoPago::route('/create'),
            'edit' => EditMetodoPago::route('/{record}/edit'),
        ];
    }
}
