<?php

namespace App\Filament\Admin\Resources\MetodosPago\Schemas;

use App\Models\MetodoPago;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MetodoPagoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // El código identifica al método en reportes y cortes de caja.
                // Se fija al crearlo y ya no cambia; lo que se corrige a gusto
                // es el nombre, que es lo que se muestra.
                TextInput::make('codigo')
                    ->label('Código')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true)
                    ->rule('regex:/^[a-z0-9_]+$/')
                    ->validationMessages([
                        'regex' => 'Use solo minúsculas, números y guion bajo: «deposito_bg».',
                    ])
                    ->disabled(fn (?MetodoPago $record): bool => $record !== null)
                    ->helperText(fn (?MetodoPago $record): string => $record === null
                        ? 'Identificador interno. No podrá cambiarse después de crearlo.'
                        : 'El código no cambia: los cortes de caja ya emitidos lo referencian.'),

                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(50)
                    ->helperText('Como aparece en el recibo: «Depósito bancario».'),

                Toggle::make('requiere_referencia')
                    ->label('Exige número de referencia')
                    ->helperText('Actívelo para cheque, depósito o transferencia: al cobrar se pedirá el número y sin él no se registra el pago.'),

                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true)
                    ->helperText('Un método inactivo deja de ofrecerse al cobrar; los pagos ya registrados no se tocan.'),
            ]);
    }
}
