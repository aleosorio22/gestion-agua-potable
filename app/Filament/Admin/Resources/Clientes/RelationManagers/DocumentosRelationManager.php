<?php

namespace App\Filament\Admin\Resources\Clientes\RelationManagers;

use App\Filament\Admin\Resources\Documentos\Schemas\DocumentoForm;
use App\Filament\Admin\Resources\Documentos\Tables\DocumentosTable;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * El expediente del vecino, dentro de su propia ficha.
 *
 * Es donde se carga en la práctica: la secretaria tiene al cliente enfrente,
 * con el DPI y la escritura en la mano. El titular no se vuelve a preguntar
 * —lo fija la relación— y el selector de propiedad ya sale acotado a las suyas.
 */
class DocumentosRelationManager extends RelationManager
{
    protected static string $relationship = 'documentos';

    protected static ?string $title = 'Expediente';

    protected static ?string $modelLabel = 'documento';

    protected static ?string $pluralModelLabel = 'documentos';

    public function form(Schema $schema): Schema
    {
        return DocumentoForm::configure($schema, conCliente: false);
    }

    public function table(Table $table): Table
    {
        return DocumentosTable::configure($table, conCliente: false)
            ->headerActions([
                CreateAction::make()->label('Cargar documento'),
            ]);
    }
}
