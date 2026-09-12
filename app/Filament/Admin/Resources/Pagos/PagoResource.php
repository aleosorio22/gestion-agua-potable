<?php

namespace App\Filament\Admin\Resources\Pagos;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\Pagos\Pages\ListPagos;
use App\Filament\Admin\Resources\Pagos\Tables\PagosTable;
use App\Models\Pago;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Los pagos no se crean ni se editan desde aquí: nacen de cobrar una boleta,
 * por `RegistradorPagos`, y una vez emitido el recibo solo admiten revertirse.
 */
class PagoResource extends Resource
{
    protected static ?string $model = Pago::class;

    protected static ?string $slug = 'pagos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Operacion;

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'pago';

    protected static ?string $pluralModelLabel = 'pagos';

    protected static ?string $recordTitleAttribute = 'folio';

    public static function table(Table $table): Table
    {
        return PagosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPagos::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
