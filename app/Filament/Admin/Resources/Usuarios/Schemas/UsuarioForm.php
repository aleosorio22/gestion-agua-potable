<?php

namespace App\Filament\Admin\Resources\Usuarios\Schemas;

use App\Filament\Admin\Support\CandadosDeUsuario;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsuarioForm
{
    /**
     * Si entre los roles elegidos está el de campo.
     *
     * Llegan como ids porque el selector cuelga de la relación, así que hay que
     * resolverlos contra la tabla en vez de comparar nombres.
     *
     * @param  mixed  $roles
     */
    protected static function incluyeRolLector($roles): bool
    {
        return Role::query()
            ->whereKey(is_array($roles) ? $roles : [])
            ->where('name', 'Lector')
            ->exists();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quién es')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->unique(User::class, ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Ya hay una cuenta con ese correo.',
                            ])
                            ->helperText('Es con lo que inicia sesión.'),
                    ]),

                Section::make('Contraseña')
                    ->description(fn (string $operation): string => $operation === 'create'
                        ? 'La va a necesitar para su primer ingreso.'
                        : 'Déjela vacía para conservar la actual. Solo escriba algo si la va a cambiar.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            // Vacío significa «no la cambies», no «bórrala»:
                            // sin esto, guardar el formulario sin tocar este
                            // campo dejaría a la persona sin poder entrar.
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->same('password_confirmation')
                            ->validationMessages([
                                'same' => 'Las dos contraseñas no coinciden.',
                                'min' => 'Use al menos 8 caracteres.',
                            ]),

                        TextInput::make('password_confirmation')
                            ->label('Repita la contraseña')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ]),

                Section::make('Qué puede hacer')
                    ->columns(2)
                    ->schema([
                        Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->native(false)
                            ->live()
                            // El rol Cliente se otorga desde el acceso al
                            // portal, no acá: asignarlo aquí crearía una cuenta
                            // sin `cliente_accesos`, que no entra a ningún lado.
                            ->options(fn (): array => Role::query()
                                ->where('name', '!=', 'Cliente')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->helperText('Define a qué pantallas entra. El acceso de un vecino al portal se otorga desde su ficha de cliente.'),

                        Select::make('sectores')
                            ->label('Sectores que recorre')
                            ->relationship('sectores', 'nombre')
                            ->multiple()
                            ->preload()
                            ->native(false)
                            ->columnSpanFull()
                            // Solo tiene sentido para quien sale a campo.
                            ->visible(fn (Get $get): bool => static::incluyeRolLector($get('roles')))
                            ->helperText('Déjelo vacío para que recorra todo el padrón. Los predios sin sector aparecen en la ruta de cualquier lector, para que ninguno quede sin leer.'),

                        Toggle::make('activo')
                            ->label('Cuenta activa')
                            ->default(true)
                            ->disabled(fn (?User $record): bool => $record !== null
                                && CandadosDeUsuario::motivoParaNoDesactivar($record) !== null)
                            ->helperText(fn (?User $record): string => ($record
                                ? CandadosDeUsuario::motivoParaNoDesactivar($record)
                                : null)
                                ?? 'Una cuenta inactiva conserva sus roles y su historial, pero no puede iniciar sesión.'),
                    ]),
            ]);
    }
}
