<?php

namespace App\Filament\Admin\Resources\SeriesDocumento\Schemas;

use App\Models\SerieDocumento;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class SerieDocumentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->description('Qué documento numera esta serie.')
                    ->columns(2)
                    ->schema([
                        Select::make('tipo_documento')
                            ->label('Tipo de documento')
                            ->required()
                            ->native(false)
                            ->options([
                                'boleta' => 'Boleta de cobro',
                                'recibo_pago' => 'Recibo de pago',
                            ])
                            ->disabled(fn (?SerieDocumento $record): bool => $record?->haEmitido() ?? false),

                        TextInput::make('codigo')
                            ->label('Código de la serie')
                            ->required()
                            ->maxLength(20)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                                    ->where('tipo_documento', $get('tipo_documento')),
                            )
                            ->validationMessages([
                                'unique' => 'Ya existe una serie con ese código para ese tipo de documento.',
                            ])
                            ->helperText('Identificador interno de la serie, para distinguirla de otras del mismo tipo.')
                            ->disabled(fn (?SerieDocumento $record): bool => $record?->haEmitido() ?? false),
                    ]),

                Section::make('Formato del folio')
                    ->description('Cómo se arma el número que sale impreso. Se congela en cuanto la serie entrega su primer documento: los folios ya en manos del vecino no se pueden reescribir.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('prefijo')
                            ->label('Prefijo')
                            ->maxLength(10)
                            ->default('')
                            // La columna es NOT NULL: vacío es '', no null.
                            ->dehydrateStateUsing(fn (?string $state): string => (string) $state)
                            ->live(onBlur: true)
                            ->placeholder('BOL')
                            ->disabled(fn (?SerieDocumento $record): bool => $record?->haEmitido() ?? false),

                        TextInput::make('separador')
                            ->label('Separador')
                            ->maxLength(5)
                            ->default('')
                            ->dehydrateStateUsing(fn (?string $state): string => (string) $state)
                            ->live(onBlur: true)
                            ->placeholder('-')
                            ->helperText('Va una sola vez, entre el prefijo y el número. Déjelo vacío para pegarlos.')
                            ->disabled(fn (?SerieDocumento $record): bool => $record?->haEmitido() ?? false),

                        TextInput::make('longitud_numero')
                            ->label('Dígitos del correlativo')
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(12)
                            ->default(6)
                            ->live(onBlur: true)
                            ->helperText('Se rellena con ceros a la izquierda hasta llegar a esta cantidad.')
                            ->disabled(fn (?SerieDocumento $record): bool => $record?->haEmitido() ?? false),

                        Toggle::make('incluye_anio')
                            ->label('Incluir el año en el folio')
                            ->live()
                            ->disabled(fn (?SerieDocumento $record): bool => $record?->haEmitido() ?? false),

                        Toggle::make('reinicia_cada_anio')
                            ->label('Reiniciar el correlativo cada año')
                            ->helperText('Al cambiar de año el contador vuelve a 1. Actívelo solo junto con el año en el folio, o se repetirán números.')
                            ->disabled(fn (?SerieDocumento $record): bool => $record?->haEmitido() ?? false),

                        Placeholder::make('vista_previa')
                            ->label('Así se verá el folio')
                            ->columnSpanFull()
                            ->content(fn (Get $get): string => self::previsualizar($get)),
                    ]),

                Section::make('Estado del correlativo')
                    ->description('El contador lo mueve el sistema al emitir. Solo se fija aquí al abrir la serie, para arrancar donde quedó la numeración en papel.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('ejercicio')
                            ->label('Ejercicio')
                            ->required()
                            ->integer()
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->default(fn (): int => (int) now()->year)
                            ->live(onBlur: true)
                            ->disabled(fn (?SerieDocumento $record): bool => $record !== null),

                        TextInput::make('siguiente_numero')
                            ->label('Siguiente número')
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->live(onBlur: true)
                            ->helperText('El número que llevará el próximo documento.')
                            ->disabled(fn (?SerieDocumento $record): bool => $record !== null),

                        Toggle::make('activa')
                            ->label('Activa')
                            ->default(true)
                            ->helperText('Solo puede haber una serie activa por tipo de documento: es la que se usa al emitir.'),
                    ]),
            ]);
    }

    /**
     * Arma el folio de ejemplo con lo que hay en el formulario, sin tocar la
     * base de datos. Es la misma lógica de SerieDocumento::formatearFolio().
     */
    private static function previsualizar(Get $get): string
    {
        $serie = new SerieDocumento([
            'prefijo' => (string) ($get('prefijo') ?? ''),
            'separador' => (string) ($get('separador') ?? ''),
            'incluye_anio' => (bool) $get('incluye_anio'),
            'longitud_numero' => max(1, (int) ($get('longitud_numero') ?: 6)),
        ]);

        $numero = max(1, (int) ($get('siguiente_numero') ?: 1));
        $ejercicio = (int) ($get('ejercicio') ?: now()->year);

        return $serie->formatearFolio($numero, $ejercicio);
    }
}
