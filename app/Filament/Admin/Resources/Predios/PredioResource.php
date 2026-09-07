<?php

namespace App\Filament\Admin\Resources\Predios;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\Predios\Pages\CreatePredio;
use App\Filament\Admin\Resources\Predios\Pages\EditPredio;
use App\Filament\Admin\Resources\Predios\Pages\ListPredios;
use App\Filament\Admin\Resources\Predios\Schemas\PredioForm;
use App\Filament\Admin\Resources\Predios\Tables\PrediosTable;
use App\Models\Predio;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PredioResource extends Resource
{
    protected static ?string $model = Predio::class;

    protected static ?string $slug = 'predios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Padron;

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'predio';

    protected static ?string $pluralModelLabel = 'predios';

    public static function form(Schema $schema): Schema
    {
        return PredioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrediosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPredios::route('/'),
            'create' => CreatePredio::route('/create'),
            'edit' => EditPredio::route('/{record}/edit'),
        ];
    }

    /**
     * El predio no tiene nombre propio: lo identifica su dirección, que se
     * arma de varias columnas. Por eso el título es el accesor y no un
     * `$recordTitleAttribute`.
     */
    public static function getRecordTitle(?Model $record): ?string
    {
        if ($record === null) {
            return null;
        }

        return $record->direccion_completa ?: "Predio #{$record->getKey()}";
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['aldea', 'calle', 'numero_casa', 'referencia'];
    }

    /**
     * Un predio eliminado tiene que poder abrirse para restaurarlo.
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
