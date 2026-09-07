<?php

namespace App\Filament\Admin\Resources\Predios\Schemas;

use App\Models\Sector;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PredioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ubicación')
                    ->description('La dirección va en piezas separadas y no como texto libre: es lo que permite agrupar por sector y armar la ruta de lectura.')
                    ->columns(2)
                    ->schema([
                        Select::make('sector_id')
                            ->label('Sector')
                            ->relationship('sector', 'nombre', fn ($query) => $query->activos()->orderBy('orden'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Sin sector asignado')
                            ->helperText('En qué parte del recorrido cae este predio.')
                            ->createOptionForm([
                                TextInput::make('nombre')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(Sector::class),

                                TextInput::make('orden')
                                    ->label('Orden de recorrido')
                                    ->required()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0),
                            ]),

                        TextInput::make('aldea')
                            ->label('Aldea o caserío')
                            ->maxLength(100),

                        TextInput::make('calle')
                            ->label('Calle o avenida')
                            ->maxLength(50),

                        TextInput::make('numero_casa')
                            ->label('Número de casa')
                            ->maxLength(20)
                            ->helperText('Como está pintado en la fachada. Ej.: 1-31.'),

                        TextInput::make('zona')
                            ->label('Zona')
                            ->maxLength(10),
                    ]),

                Section::make('Cómo llegar')
                    ->description('Lo que el lector necesita para dar con la casa cuando la dirección no alcanza.')
                    ->schema([
                        TextInput::make('referencia')
                            ->label('Referencia')
                            ->maxLength(255)
                            ->helperText('Ej.: frente a la tienda, portón azul, a la par de la escuela.'),
                    ]),

                Section::make('Coordenadas')
                    ->description('Opcional. Sirve para las rutas de lectura por geolocalización, que hoy están fuera de alcance.')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('latitud')
                            ->label('Latitud')
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90)
                            ->step(0.0000001),

                        TextInput::make('longitud')
                            ->label('Longitud')
                            ->numeric()
                            ->minValue(-180)
                            ->maxValue(180)
                            ->step(0.0000001),
                    ]),
            ]);
    }
}
