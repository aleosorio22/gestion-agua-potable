<?php

namespace App\Filament\Admin\Resources\SeriesDocumento;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\SeriesDocumento\Pages\CreateSerieDocumento;
use App\Filament\Admin\Resources\SeriesDocumento\Pages\EditSerieDocumento;
use App\Filament\Admin\Resources\SeriesDocumento\Pages\ListSeriesDocumento;
use App\Filament\Admin\Resources\SeriesDocumento\Schemas\SerieDocumentoForm;
use App\Filament\Admin\Resources\SeriesDocumento\Tables\SeriesDocumentoTable;
use App\Models\SerieDocumento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SerieDocumentoResource extends Resource
{
    protected static ?string $model = SerieDocumento::class;

    protected static ?string $slug = 'series-documento';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Catalogos;

    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'serie de documento';

    protected static ?string $pluralModelLabel = 'series de documento';

    protected static ?string $recordTitleAttribute = 'codigo';

    public static function form(Schema $schema): Schema
    {
        return SerieDocumentoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeriesDocumentoTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeriesDocumento::route('/'),
            'create' => CreateSerieDocumento::route('/create'),
            'edit' => EditSerieDocumento::route('/{record}/edit'),
        ];
    }
}
