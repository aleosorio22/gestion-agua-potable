<?php

namespace App\Filament\Admin\Resources\Periodos;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\Periodos\Pages\CreatePeriodo;
use App\Filament\Admin\Resources\Periodos\Pages\EditPeriodo;
use App\Filament\Admin\Resources\Periodos\Pages\ListPeriodos;
use App\Filament\Admin\Resources\Periodos\Schemas\PeriodoForm;
use App\Filament\Admin\Resources\Periodos\Tables\PeriodosTable;
use App\Models\Periodo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PeriodoResource extends Resource
{
    protected static ?string $model = Periodo::class;

    protected static ?string $slug = 'periodos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Operacion;

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'período';

    protected static ?string $pluralModelLabel = 'períodos';

    protected static ?string $recordTitleAttribute = 'etiqueta';

    public static function form(Schema $schema): Schema
    {
        return PeriodoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PeriodosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPeriodos::route('/'),
            'create' => CreatePeriodo::route('/create'),
            'edit' => EditPeriodo::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Periodo::abiertos()->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Períodos abiertos';
    }
}
