<?php

namespace App\Filament\Admin\Resources\Clientes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo')
                    ->required(),

                TextInput::make('nombre')
                    ->required(),

                TextInput::make('nit'),

                TextInput::make('dpi')
                    ->label('DPI')
                    ->maxLength(13)
                    ->rules([
                        'digits:13',
                        'regex:/^[0-9]{13}$/',
                    ]),

                TextInput::make('telefono')
                    ->tel(),

                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email(),

                TextInput::make('direccion_notificacion'),

                Select::make('estado')
                    ->options([
                        'activo' => 'Activo',
                        'inactivo' => 'Inactivo',
                    ])
                    ->default('activo')
                    ->required(),
            ]);
    }
}
