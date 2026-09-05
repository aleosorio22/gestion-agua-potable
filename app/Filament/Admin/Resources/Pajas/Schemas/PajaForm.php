<?php

namespace App\Filament\Admin\Resources\Pajas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PajaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->helperText('El tamaño contratado, tal como aparece en el convenio: «1 paja», «1/2 paja».'),

                TextInput::make('equivalencia_m3')
                    ->label('Equivalencia')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->suffix('m³')
                    ->helperText('Volumen que cubre la cuota fija. Lo que pase de aquí se cobra como excedente.'),

                Toggle::make('activo')
                    ->label('Activa')
                    ->default(true)
                    ->helperText('Una paja inactiva deja de ofrecerse en contadores nuevos; los que ya la tienen siguen igual.'),
            ]);
    }
}
