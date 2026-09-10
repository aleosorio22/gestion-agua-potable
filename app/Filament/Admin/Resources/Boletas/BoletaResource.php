<?php

namespace App\Filament\Admin\Resources\Boletas;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\Boletas\Pages\ListBoletas;
use App\Filament\Admin\Resources\Boletas\Tables\BoletasTable;
use App\Models\Boleta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Las boletas no se crean ni se editan desde aquí: nacen de una lectura, por
 * `EmisorBoletas`, y una vez emitidas solo admiten anularse. Por eso el
 * Resource no tiene formulario ni páginas de alta.
 */
class BoletaResource extends Resource
{
    protected static ?string $model = Boleta::class;

    protected static ?string $slug = 'boletas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Operacion;

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'boleta';

    protected static ?string $pluralModelLabel = 'boletas';

    protected static ?string $recordTitleAttribute = 'folio';

    public static function table(Table $table): Table
    {
        return BoletasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBoletas::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pendientes = Boleta::query()->pendientes()->count();

        return $pendientes > 0 ? (string) $pendientes : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Boletas pendientes de cobro';
    }
}
