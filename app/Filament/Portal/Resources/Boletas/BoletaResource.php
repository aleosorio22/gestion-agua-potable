<?php

namespace App\Filament\Portal\Resources\Boletas;

use App\Filament\Portal\Resources\Boletas\Pages\ListBoletas;
use App\Filament\Portal\Resources\Boletas\Tables\BoletasTable;
use App\Models\Boleta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BoletaResource extends Resource
{
    protected static ?string $model = Boleta::class;

    protected static ?string $slug = 'boletas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $modelLabel = 'boleta';

    protected static ?string $pluralModelLabel = 'mis boletas';

    protected static ?string $recordTitleAttribute = 'folio';

    /**
     * El portal es de solo lectura: nunca se crea, edita ni borra una
     * boleta desde aquí, solo se consulta.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Cada Cliente ve exclusivamente sus propias boletas. Esto aplica a
     * TODAS las consultas del Resource (listado, conteos, búsquedas), no
     * solo a la tabla visible — es la restricción por fila que no se puede
     * lograr con permisos de Shield, tiene que vivir aquí.
     */
    public static function getEloquentQuery(): Builder
    {
        $clienteId = auth()->user()?->cliente()?->id;

        return parent::getEloquentQuery()
            ->where('cliente_id', $clienteId ?? 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

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
}
