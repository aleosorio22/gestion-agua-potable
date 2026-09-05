<?php

namespace App\Filament\Admin\Resources\TiposDocumento\Schemas;

use App\Models\TipoDocumento;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TipoDocumentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo')
                    ->label('Código')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true)
                    ->rule('regex:/^[a-z0-9_]+$/')
                    ->validationMessages([
                        'regex' => 'Use solo minúsculas, números y guion bajo: «recibo_luz».',
                    ])
                    ->disabled(fn (?TipoDocumento $record): bool => $record !== null)
                    ->helperText(fn (?TipoDocumento $record): string => $record === null
                        ? 'Identificador interno. No podrá cambiarse después de crearlo.'
                        : 'El código no cambia: los expedientes ya archivados lo referencian.'),

                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(80)
                    ->helperText('Como se le pide al vecino: «Recibo de energía eléctrica».'),

                Toggle::make('respalda_predio')
                    ->label('Respalda un predio')
                    ->helperText('Actívelo si el documento prueba la propiedad (recibo de luz, escritura): al archivarlo se pedirá a qué predio corresponde. Déjelo apagado para los que solo identifican a la persona, como DPI o NIT.'),

                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true)
                    ->helperText('Un tipo inactivo deja de ofrecerse al archivar documentos nuevos.'),
            ]);
    }
}
