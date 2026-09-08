<?php

namespace App\Filament\Admin\Resources\Lecturas\Schemas;

use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Periodo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

/**
 * El formulario del lector.
 *
 * `LecturaObserver` ya protege las invariantes lanzando excepciones, pero una
 * excepción en Filament es una pantalla de error: cada regla se repite acá
 * como validación para que el lector lea qué hizo mal, en su idioma, sin
 * perder lo que ya escribió.
 */
class LecturaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Visita')
                    ->description('Qué contador se leyó y en qué ciclo entra la lectura.')
                    ->columns(2)
                    ->schema([
                        Select::make('periodo_id')
                            ->label('Período')
                            ->relationship('periodo', 'id', fn ($query) => $query->abiertos()->orderByDesc('fecha_inicio'))
                            ->getOptionLabelFromRecordUsing(fn (Periodo $record): string => $record->etiqueta)
                            ->default(fn (): ?int => Periodo::abiertos()->orderByDesc('fecha_inicio')->value('id'))
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText('Solo aparecen los períodos abiertos.'),

                        Select::make('contador_id')
                            ->label('Contador')
                            ->relationship('contador', 'codigo', fn ($query) => $query->activos())
                            ->getOptionLabelFromRecordUsing(fn (Contador $record): string => "{$record->codigo} — ".($record->cliente?->nombre ?? 'sin titular'))
                            ->searchable(['codigo'])
                            ->preload()
                            ->required()
                            ->native(false)
                            ->live()
                            // La lectura anterior no se teclea: tiene que ser
                            // exactamente la última registrada del contador o
                            // el observer rechaza el guardado.
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                $contador = $state ? Contador::find($state) : null;

                                $set('lectura_anterior', (float) ($contador?->ultimaLectura()?->lectura_actual ?? 0));
                            })
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                                    ->where('periodo_id', $get('periodo_id')),
                            )
                            ->validationMessages([
                                'unique' => 'Ese contador ya tiene lectura registrada en este período.',
                            ]),

                        DatePicker::make('fecha_lectura')
                            ->label('Fecha de la visita')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now())
                            ->maxDate(now())
                            ->validationMessages([
                                'before_or_equal' => 'La fecha de la visita no puede ser futura.',
                            ]),
                    ]),

                Section::make('Marcador del medidor')
                    ->description('El consumo lo calcula la base de datos como la diferencia entre ambas cifras; no se escribe a mano.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('lectura_anterior')
                            ->label('Lectura anterior')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('m³')
                            ->default(0)
                            // De solo lectura, pero se guarda: `disabled()` sin
                            // `dehydrated()` mandaría null y la columna es NOT NULL.
                            ->disabled()
                            ->dehydrated()
                            ->helperText('La última registrada de este contador.'),

                        TextInput::make('lectura_actual')
                            ->label('Lectura actual')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('m³')
                            ->live(onBlur: true)
                            ->gte('lectura_anterior')
                            ->validationMessages([
                                'gte' => 'La lectura actual no puede ser menor que la anterior. Si el medidor fue reemplazado, registre el cambio de contador.',
                            ])
                            ->helperText('El número que marca el medidor hoy.'),

                        // `consumo_m3` es una columna generada (STORED): no es
                        // un campo del formulario, solo el número que el lector
                        // confirma de un vistazo antes de guardar.
                        Placeholder::make('consumo_calculado')
                            ->label('Consumo del período')
                            ->content(fn (Get $get): string => number_format(
                                max(0, (float) $get('lectura_actual') - (float) $get('lectura_anterior')),
                                2
                            ).' m³'),
                    ]),

                Section::make('Notas de campo')
                    ->schema([
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->maxLength(255)
                            ->rows(2)
                            ->helperText('Ej.: medidor empañado, portón cerrado, fuga visible.'),
                    ]),
            ])
            // Una lectura facturada es el origen del snapshot de la boleta: si
            // cambia, boleta y lectura quedan contradiciéndose.
            ->disabled(fn (?Lectura $record): bool => $record?->esta_facturada ?? false);
    }
}
