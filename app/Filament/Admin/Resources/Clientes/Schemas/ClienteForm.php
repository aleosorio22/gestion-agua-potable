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
                TextInput::make('dpi'),
                TextInput::make('telefono')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('direccion_notificacion'),
                Select::make('estado')
                    ->options(['activo' => 'Activo', 'inactivo' => 'Inactivo'])
                    ->default('activo')
                    ->required(),
            ]);
    }
}
