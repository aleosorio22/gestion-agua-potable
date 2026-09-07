<?php

namespace App\Filament\Admin\Resources\Contadores\Schemas;

use App\Filament\Admin\Resources\Predios\Schemas\PredioForm;
use App\Models\Predio;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContadorForm
{
    /**
     * @param  bool  $conTitular  Falso cuando el formulario se abre desde la ficha
     *                            del cliente: ahí el titular ya lo fija la relación
     *                            y volver a preguntarlo solo invita a equivocarse.
     */
    public static function configure(Schema $schema, bool $conTitular = true): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->description('El código es con el que el lector ubica el medidor en campo.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('codigo')
                            ->label('Código del contador')
                            ->required()
                            ->maxLength(30)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                // El código de un medidor no se reutiliza: si el
                                // que choca es uno dado de baja, lo correcto es
                                // restaurarlo, no crear otro registro con el
                                // mismo número grabado en el aparato.
                                'unique' => 'Ese código ya está registrado en otro contador. Si el contador anterior fue eliminado, restáurelo en lugar de crear uno nuevo.',
                            ])
                            ->helperText('Como viene grabado en el aparato. Ej.: CTR-00123.'),

                        DatePicker::make('fecha_instalacion')
                            ->label('Fecha de instalación')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->maxDate(now())
                            ->validationMessages([
                                'before_or_equal' => 'La fecha de instalación no puede ser futura.',
                            ]),
                    ]),

                Section::make('A quién y dónde sirve')
                    ->description('El titular es la persona que paga; el predio es la propiedad donde llega el agua. Pueden no coincidir con la dirección de notificación del cliente.')
                    ->columns(2)
                    ->schema([
                        Select::make('cliente_id')
                            ->label('Titular del servicio')
                            ->visible($conTitular)
                            ->dehydrated($conTitular)
                            ->relationship('cliente', 'nombre', fn ($query) => $query->activos())
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->codigo} — {$record->nombre}")
                            ->searchable(['codigo', 'nombre', 'dpi', 'nit'])
                            ->preload()
                            ->required()
                            ->native(false)
                            ->helperText('Solo aparecen los clientes activos.'),

                        Select::make('predio_id')
                            ->label('Predio servido')
                            ->relationship('predio')
                            ->getOptionLabelFromRecordUsing(fn (Predio $record): string => $record->direccion_completa ?: "Predio #{$record->getKey()}")
                            ->searchable(['aldea', 'calle', 'numero_casa', 'referencia'])
                            ->preload()
                            ->required()
                            ->native(false)
                            // Alta al vuelo: en ventanilla el predio nuevo
                            // aparece junto con el contador, y obligar a salir
                            // a otra pantalla para volver es lo que hace que la
                            // secretaria termine registrando direcciones a medias.
                            ->createOptionForm(fn (Schema $schema): Schema => PredioForm::configure($schema))
                            ->createOptionModalHeading('Nuevo predio'),

                        Select::make('paja_id')
                            ->label('Paja contratada')
                            ->relationship('paja', 'nombre', fn ($query) => $query->where('activo', true))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->nombre} ({$record->equivalencia_m3} m³ incluidos)")
                            ->preload()
                            ->required()
                            ->native(false)
                            ->helperText('Define cuántos m³ van incluidos antes de que empiece a cobrarse excedente.'),
                    ]),

                Section::make('Situación')
                    ->schema([
                        Select::make('estado')
                            ->label('Estado')
                            ->required()
                            ->native(false)
                            ->options([
                                'activo' => 'Activo',
                                'inactivo' => 'Inactivo',
                                'dañado' => 'Dañado',
                            ])
                            ->default('activo')
                            ->helperText('Solo los contadores activos aparecen en la ruta de lectura del período.'),
                    ]),
            ]);
    }
}
