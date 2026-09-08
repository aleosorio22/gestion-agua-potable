<?php

namespace App\Filament\Admin\Resources\Periodos\Schemas;

use App\Filament\Admin\Support\MesesDelAnio;
use App\Models\Periodo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class PeriodoForm
{
    public static function configure(Schema $schema): Schema
    {
        $sugerido = Periodo::siguienteSugerido();

        return $schema
            ->components([
                Section::make('Mes que cubre')
                    ->description('Viene propuesto el mes siguiente al último período registrado. Cámbielo solo si está reponiendo un mes atrasado.')
                    ->columns(2)
                    ->schema([
                        Select::make('mes')
                            ->label('Mes')
                            ->required()
                            ->native(false)
                            ->options(MesesDelAnio::opciones())
                            ->default($sugerido['mes'])
                            // La unicidad es del par año+mes, así que la regla
                            // tiene que mirar el otro campo o deja pasar dos
                            // «septiembre» de años distintos como duplicado.
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                                    ->where('anio', $get('anio')),
                            )
                            ->validationMessages([
                                'unique' => 'Ese mes ya tiene un período abierto.',
                            ]),

                        TextInput::make('anio')
                            ->label('Año')
                            ->required()
                            ->integer()
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->default($sugerido['anio']),
                    ]),

                Section::make('Rango de fechas')
                    ->description('Delimita qué lecturas caen dentro del ciclo. Normalmente es el mes calendario completo.')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('fecha_inicio')
                            ->label('Desde')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default($sugerido['fecha_inicio']),

                        DatePicker::make('fecha_fin')
                            ->label('Hasta')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default($sugerido['fecha_fin'])
                            ->afterOrEqual('fecha_inicio')
                            ->validationMessages([
                                'after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
                            ]),
                    ]),
            ])
            // Un período cerrado es un mes liquidado: se consulta, no se edita.
            ->disabled(fn (?Periodo $record): bool => $record?->esta_cerrado ?? false);
    }
}
