<?php

namespace App\Filament\Admin\Resources\Tarifas\Schemas;

use App\Models\Tarifa;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TarifaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('paja_id')
                    ->label('Paja')
                    ->relationship('paja', 'nombre')
                    ->required()
                    ->preload()
                    ->native(false)
                    ->disabled(fn (?Tarifa $record): bool => $record?->estaEnUso() ?? false),

                TextInput::make('monto_base')
                    ->label('Cuota fija')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('Q')
                    ->helperText('Lo que se cobra aunque el consumo no llegue a la equivalencia de la paja.')
                    ->disabled(fn (?Tarifa $record): bool => $record?->estaEnUso() ?? false),

                TextInput::make('precio_m3_excedente')
                    ->label('Precio por m³ excedente')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.0001)
                    ->prefix('Q')
                    ->helperText('Se aplica solo a los m³ que pasan de la equivalencia contratada.')
                    ->disabled(fn (?Tarifa $record): bool => $record?->estaEnUso() ?? false),

                DatePicker::make('vigente_desde')
                    ->label('Vigente desde')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    // No hay `vigente_hasta`: esta tarifa rige hasta el día
                    // anterior a la siguiente de la misma paja. Por eso basta
                    // con impedir dos que arranquen el mismo día.
                    //
                    // La comparación va con whereDate() y no con unique(): el
                    // cast `date` guarda '2026-01-01 00:00:00', así que la regla
                    // de Laravel compara contra '2026-01-01' y no encuentra
                    // nada. El choque solo aparecía al insertar.
                    ->rule(static fn (Get $get, ?Tarifa $record): Closure => static function (
                        string $atributo,
                        mixed $valor,
                        Closure $fallar
                    ) use ($get, $record): void {
                        $repetida = Tarifa::query()
                            ->where('paja_id', $get('paja_id'))
                            ->whereDate('vigente_desde', $valor)
                            ->when($record, fn ($consulta) => $consulta->whereKeyNot($record->getKey()))
                            ->exists();

                        if ($repetida) {
                            $fallar('Esa paja ya tiene una tarifa que arranca ese día.');
                        }
                    })
                    ->helperText('Rige desde esta fecha hasta que empiece la siguiente tarifa de la misma paja.')
                    ->disabled(fn (?Tarifa $record): bool => $record?->estaEnUso() ?? false),
            ]);
    }
}
