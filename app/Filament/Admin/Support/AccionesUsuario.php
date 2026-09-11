<?php

namespace App\Filament\Admin\Support;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;

class AccionesUsuario
{
    /**
     * El caso real de la oficina: alguien olvidó su contraseña y no hay correo
     * configurado para recuperarla. El administrador le pone una nueva y se la
     * dice; la persona la cambia después desde su perfil.
     */
    public static function restablecerContrasena(): Action
    {
        return Action::make('restablecerContrasena')
            ->label('Restablecer contraseña')
            ->icon(Heroicon::OutlinedKey)
            ->color('warning')
            ->modalHeading(fn (User $record): string => "Nueva contraseña para {$record->name}")
            ->modalDescription('La contraseña anterior deja de servir en cuanto confirme.')
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
            ->action(function (User $record, array $data): void {
                $record->update(['password' => Hash::make($data['password'])]);

                Notification::make()
                    ->success()
                    ->title('Contraseña cambiada')
                    ->body("Entréguesela a {$record->name} y pídale que la cambie en cuanto entre.")
                    ->send();
            });
    }

    public static function alternarActivo(): Action
    {
        return Action::make('alternarActivo')
            ->label(fn (User $record): string => $record->activo ? 'Dar de baja' : 'Reactivar')
            ->icon(fn (User $record): Heroicon => $record->activo
                ? Heroicon::OutlinedUserMinus
                : Heroicon::OutlinedUserPlus)
            ->color(fn (User $record): string => $record->activo ? 'danger' : 'success')
            ->disabled(fn (User $record): bool => CandadosDeUsuario::motivoParaNoDesactivar($record) !== null)
            ->tooltip(fn (User $record): ?string => CandadosDeUsuario::motivoParaNoDesactivar($record))
            ->requiresConfirmation()
            ->modalHeading(fn (User $record): string => $record->activo
                ? "Dar de baja a {$record->name}"
                : "Reactivar a {$record->name}")
            ->modalDescription(fn (User $record): string => $record->activo
                ? 'No podrá iniciar sesión, pero conserva sus roles y todo su historial de trabajo. Se puede revertir.'
                : 'Vuelve a poder iniciar sesión con los roles que ya tenía.')
            ->action(function (User $record): void {
                // El candado se vuelve a consultar acá: entre que se pintó la
                // pantalla y se confirmó, el otro administrador pudo darse de
                // baja y este pasó a ser el último.
                if ($motivo = CandadosDeUsuario::motivoParaNoDesactivar($record)) {
                    Notification::make()
                        ->danger()
                        ->title('No se puede dar de baja')
                        ->body($motivo)
                        ->persistent()
                        ->send();

                    return;
                }

                $record->update(['activo' => ! $record->activo]);

                Notification::make()
                    ->success()
                    ->title($record->activo ? "{$record->name} reactivado" : "{$record->name} dado de baja")
                    ->send();
            });
    }

    public static function eliminar(): DeleteAction
    {
        return DeleteAction::make()
            ->disabled(fn (User $record): bool => CandadosDeUsuario::motivoParaNoEliminar($record) !== null)
            ->tooltip(fn (User $record): ?string => CandadosDeUsuario::motivoParaNoEliminar($record))
            ->before(function (DeleteAction $action, User $record): void {
                $motivo = CandadosDeUsuario::motivoParaNoEliminar($record);

                if ($motivo === null) {
                    return;
                }

                Notification::make()
                    ->danger()
                    ->title('No se puede eliminar')
                    ->body($motivo)
                    ->persistent()
                    ->send();

                $action->cancel();
            });
    }
}
