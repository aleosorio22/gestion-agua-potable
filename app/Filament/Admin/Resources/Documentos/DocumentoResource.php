<?php

namespace App\Filament\Admin\Resources\Documentos;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\Documentos\Pages\CreateDocumento;
use App\Filament\Admin\Resources\Documentos\Pages\EditDocumento;
use App\Filament\Admin\Resources\Documentos\Pages\ListDocumentos;
use App\Filament\Admin\Resources\Documentos\Schemas\DocumentoForm;
use App\Filament\Admin\Resources\Documentos\Tables\DocumentosTable;
use App\Models\Documento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DocumentoResource extends Resource
{
    protected static ?string $model = Documento::class;

    protected static ?string $slug = 'documentos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperClip;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Padron;

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'documento';

    protected static ?string $pluralModelLabel = 'documentos';

    protected static ?string $recordTitleAttribute = 'nombre_original';

    public static function form(Schema $schema): Schema
    {
        return DocumentoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentos::route('/'),
            'create' => CreateDocumento::route('/create'),
            'edit' => EditDocumento::route('/{record}/edit'),
        ];
    }
}
