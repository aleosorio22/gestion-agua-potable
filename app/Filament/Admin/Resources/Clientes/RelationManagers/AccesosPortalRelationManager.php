<?php

namespace App\Filament\Admin\Resources\Clientes\RelationManagers;

use App\Models\Cliente;
use App\Models\ClienteAcceso;
use App\Models\User;
use App\Services\AccesoAlPortal;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * El acceso del vecino al portal de autoservicio, desde su propia ficha.
 *
 * Muestra el historial completo —quién otorgó, quién revocó y cuándo— porque
 * para eso existe `cliente_accesos`: si solo interesara el estado actual,
 * bastaba una columna en `clientes`.
 */
class AccesosPortalRelationManager extends RelationManager
{
    protected static string $relationship = 'accesos';

    protected static ?string $title = 'Acceso al portal';

    protected static ?string $modelLabel = 'acceso';

    protected static ?string $pluralModelLabel = 'accesos';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Cuenta')
                    ->description(fn (ClienteAcceso $record): ?string => $record->user?->email),

                TextColumn::make('otorgado_en')
                    ->label('Otorgado')
                    ->dateTime('d/m/Y H:i')
                    ->description(fn (ClienteAcceso $record): ?string => $record->otorgadoPor?->name),

                TextColumn::make('revocado_en')
                    ->label('Revocado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->description(fn (ClienteAcceso $record): ?string => $record->revocadoPor?->name),

                TextColumn::make('vigente')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (ClienteAcceso $record): string => $record->revocado_en === null ? 'Vigente' : 'Revocado')
                    ->color(fn (ClienteAcceso $record): string => $record->revocado_en === null ? 'success' : 'gray'),
            ])
            ->defaultSort('otorgado_en', 'desc')
            ->headerActions([
                $this->accionOtorgar(),
            ])
            ->recordActions([
                $this->accionRevocar(),
                $this->accionRestablecerContrasena(),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Sin acceso al portal')
            ->emptyStateDescription('Este vecino todavía no puede consultar sus boletas en línea.');
    }

    protected function accionOtorgar(): Action
    {
        return Action::make('otorgar')
            ->label('Otorgar acceso')
            ->icon(Heroicon::OutlinedKey)
            ->color('success')
            // Un cliente tiene un acceso vigente a la vez; para cambiar de
            // cuenta primero se revoca la anterior.
            ->visible(fn (): bool => ! $this->clienteDeLaFicha()->accesoActivo()->exists())
            ->modalHeading(fn (): string => "Dar acceso al portal a {$this->clienteDeLaFicha()->nombre}")
            ->modalSubmitActionLabel('Otorgar')
            ->schema([
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->required()
                    ->email()
                    ->live(onBlur: true)
                    ->default(fn (): ?string => $this->clienteDeLaFicha()->email)
                    ->helperText('Es con lo que el vecino inicia sesión en el portal.'),

                // Si el correo ya tiene cuenta se vincula esa, no se crea otra:
                // pedir nombre y contraseña ahí sería mentirle al usuario.
                Placeholder::make('aviso_cuenta_existente')
                    ->label('')
                    ->visible(fn (Get $get): bool => $this->cuentaDe($get('email')) !== null)
                    ->content(fn (Get $get): string => 'Ya existe una cuenta con ese correo ('
                        .$this->cuentaDe($get('email'))?->name
                        .'). Se le dará acceso a esa cuenta, conservando su contraseña actual.'),

                TextInput::make('name')
                    ->label('Nombre de la cuenta')
                    ->required(fn (Get $get): bool => $this->cuentaDe($get('email')) === null)
                    ->visible(fn (Get $get): bool => $this->cuentaDe($get('email')) === null)
                    ->default(fn (): string => $this->clienteDeLaFicha()->nombre)
                    ->maxLength(255),

                TextInput::make('password')
                    ->label('Contraseña inicial')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (Get $get): bool => $this->cuentaDe($get('email')) === null)
                    ->visible(fn (Get $get): bool => $this->cuentaDe($get('email')) === null)
                    ->validationMessages(['min' => 'Use al menos 8 caracteres.'])
                    ->helperText('Entréguesela al vecino y pídale que la cambie al entrar.'),
            ])
            ->action(function (array $data): void {
                try {
                    app(AccesoAlPortal::class)->otorgar(
                        $this->clienteDeLaFicha(),
                        $data['email'],
                        $data['name'] ?? null,
                        $data['password'] ?? null,
                    );
                } catch (RuntimeException $error) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo otorgar el acceso')
                        ->body($error->getMessage())
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Acceso otorgado')
                    ->body('Ya puede entrar al portal y consultar sus boletas.')
                    ->send();
            });
    }

    protected function accionRevocar(): Action
    {
        return Action::make('revocar')
            ->label('Revocar')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (ClienteAcceso $record): bool => $record->revocado_en === null)
            ->requiresConfirmation()
            ->modalHeading('Revocar el acceso al portal')
            ->modalDescription('La cuenta deja de entrar al portal de inmediato. No se borra ni se pierde el historial: queda registrado quién revocó y cuándo, y más adelante se le puede volver a otorgar.')
            ->modalSubmitActionLabel('Revocar')
            ->action(function (ClienteAcceso $record): void {
                app(AccesoAlPortal::class)->revocar($record);

                Notification::make()
                    ->success()
                    ->title('Acceso revocado')
                    ->send();
            });
    }

    /**
     * Las cuentas del portal no se administran desde la pantalla de Usuarios
     * —esa es de personal—, así que el olvido de contraseña de un vecino se
     * resuelve acá.
     */
    protected function accionRestablecerContrasena(): Action
    {
        return Action::make('restablecerContrasenaPortal')
            ->label('Restablecer contraseña')
            ->icon(Heroicon::OutlinedKey)
            ->color('gray')
            ->visible(fn (ClienteAcceso $record): bool => $record->revocado_en === null)
            ->modalHeading(fn (ClienteAcceso $record): string => "Nueva contraseña para {$record->user->name}")
            ->modalSubmitActionLabel('Cambiar la contraseña')
            ->schema([
                TextInput::make('password')
                    ->label('Nueva contraseña')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->same('password_confirmation')
                    ->validationMessages([
                        'same' => 'Las dos contraseñas no coinciden.',
                        'min' => 'Use al menos 8 caracteres.',
                    ]),

                TextInput::make('password_confirmation')
                    ->label('Repita la contraseña')
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->action(function (ClienteAcceso $record, array $data): void {
                $record->user->update(['password' => Hash::make($data['password'])]);

                Notification::make()
                    ->success()
                    ->title('Contraseña cambiada')
                    ->body('Entréguesela al vecino y pídale que la cambie al entrar.')
                    ->send();
            });
    }

    private function clienteDeLaFicha(): Cliente
    {
        return $this->getOwnerRecord();
    }

    private function cuentaDe(?string $correo): ?User
    {
        return blank($correo) ? null : User::where('email', $correo)->first();
    }
}
