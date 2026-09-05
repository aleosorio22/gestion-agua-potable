<?php

namespace App\Filament\Admin\Resources\TiposDocumento;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\TiposDocumento\Pages\CreateTipoDocumento;
use App\Filament\Admin\Resources\TiposDocumento\Pages\EditTipoDocumento;
use App\Filament\Admin\Resources\TiposDocumento\Pages\ListTiposDocumento;
use App\Filament\Admin\Resources\TiposDocumento\Schemas\TipoDocumentoForm;
use App\Filament\Admin\Resources\TiposDocumento\Tables\TiposDocumentoTable;
use App\Models\TipoDocumento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TipoDocumentoResource extends Resource
{
    protected static ?string $model = TipoDocumento::class;

    protected static ?string $slug = 'tipos-documento';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Catalogos;

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'tipo de documento';

    protected static ?string $pluralModelLabel = 'tipos de documento';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return TipoDocumentoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TiposDocumentoTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTiposDocumento::route('/'),
            'create' => CreateTipoDocumento::route('/create'),
            'edit' => EditTipoDocumento::route('/{record}/edit'),
        ];
    }
}
