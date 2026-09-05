<?php

namespace App\Filament\Admin\Resources\Sectores\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SectorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->helperText('Como se le conoce en la comunidad: «Aldea El Porvenir», «Sector 2».'),

                TextInput::make('descripcion')
                    ->label('Descripción')
                    ->maxLength(255),

                TextInput::make('orden')
                    ->label('Orden de recorrido')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->maxValue(32767)
                    ->default(0)
                    ->helperText('En qué posición visita este sector el lector. El menor va primero.'),

                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true)
                    ->helperText('Un sector inactivo deja de ofrecerse al registrar predios nuevos.'),
            ]);
    }
}
