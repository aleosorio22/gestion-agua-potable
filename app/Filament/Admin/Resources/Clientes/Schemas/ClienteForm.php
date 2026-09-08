<?php

namespace App\Filament\Admin\Resources\Clientes\Schemas;

use App\Models\Cliente;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::campos());
    }

    /**
     * Los datos de la persona, sueltos, para que el alta guiada los monte como
     * un paso del asistente sin copiarlos.
     *
     * @return array<int, Component>
     */
    public static function campos(): array
    {
        return [
            Section::make('Identificación')
                ->description('Con qué se le busca en ventanilla.')
                ->columns(2)
                ->schema([
                    TextInput::make('codigo')
                        ->label('Código')
                        ->required()
                        ->maxLength(20)
                        ->unique(Cliente::class, ignoreRecord: true)
                        ->validationMessages([
                            'unique' => 'Ya existe un cliente con ese código. Si no aparece en el listado, revise los eliminados.',
                        ])
                        ->helperText('Código corto y estable que el vecino cita en ventanilla. Ej.: CLI-0001.'),

                    TextInput::make('nombre')
                        ->label('Nombre completo')
                        ->required()
                        ->maxLength(150)
                        ->helperText('Como aparece en el documento de identidad.'),
                ]),

            Section::make('Documentos de identidad')
                ->description('Opcionales, pero no se pueden repetir entre clientes: son lo que evita dar de alta dos veces a la misma persona.')
                ->columns(2)
                ->schema([
                    TextInput::make('dpi')
                        ->label('DPI')
                        ->maxLength(13)
                        ->rule('digits:13')
                        ->unique(Cliente::class, ignoreRecord: true)
                        ->validationMessages([
                            'digits' => 'El DPI debe tener exactamente 13 dígitos.',
                            'unique' => 'Ese DPI ya está registrado en otro cliente.',
                        ])
                        ->helperText('13 dígitos, sin espacios ni guiones.'),

                    TextInput::make('nit')
                        ->label('NIT')
                        ->maxLength(20)
                        ->unique(Cliente::class, ignoreRecord: true)
                        ->validationMessages([
                            'unique' => 'Ese NIT ya está registrado en otro cliente.',
                        ])
                        ->helperText('Con guion antes del dígito verificador. Ej.: 1234567-8.'),
                ]),

            Section::make('Contacto')
                ->description('Por dónde se le avisa del corte, del vencimiento o de una lectura pendiente.')
                ->columns(2)
                ->schema([
                    TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(20),

                    TextInput::make('email')
                        ->label('Correo electrónico')
                        ->email()
                        ->maxLength(150)
                        ->validationMessages([
                            'email' => 'Escriba un correo electrónico válido.',
                        ]),

                    Textarea::make('direccion_notificacion')
                        ->label('Dirección de notificación')
                        ->maxLength(255)
                        ->rows(2)
                        ->columnSpanFull()
                        ->helperText('Dónde se le notifica a la persona. La dirección donde llega el agua va en el predio, no aquí.'),
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
                        ])
                        ->default('activo')
                        ->helperText('Inactivo es la baja real del servicio: el cliente se conserva con todo su historial, pero deja de ofrecerse al registrar contadores nuevos.'),
                ]),
        ];
    }
}
