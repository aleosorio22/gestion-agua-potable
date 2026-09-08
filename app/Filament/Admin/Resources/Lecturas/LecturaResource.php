<?php

namespace App\Filament\Admin\Resources\Lecturas;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\Lecturas\Pages\CreateLectura;
use App\Filament\Admin\Resources\Lecturas\Pages\EditLectura;
use App\Filament\Admin\Resources\Lecturas\Pages\ListLecturas;
use App\Filament\Admin\Resources\Lecturas\Schemas\LecturaForm;
use App\Filament\Admin\Resources\Lecturas\Tables\LecturasTable;
use App\Models\Lectura;
use App\Models\Periodo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LecturaResource extends Resource
{
    protected static ?string $model = Lectura::class;

    protected static ?string $slug = 'lecturas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Operacion;

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'lectura';

    protected static ?string $pluralModelLabel = 'lecturas';

    public static function form(Schema $schema): Schema
    {
        return LecturaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LecturasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLecturas::route('/'),
            'create' => CreateLectura::route('/create'),
            'edit' => EditLectura::route('/{record}/edit'),
        ];
    }

    /**
     * Sin período abierto no hay dónde registrar: el alta se apaga en vez de
     * dejar al lector llenar el formulario para chocar al guardar.
     */
    public static function canCreate(): bool
    {
        return parent::canCreate() && Periodo::abiertos()->exists();
    }
}
