<?php

namespace App\Filament\Admin\Resources\Usuarios\Pages;

use App\Filament\Admin\Resources\Usuarios\UsuarioResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUsuario extends CreateRecord
{
    protected static string $resource = UsuarioResource::class;

    public function getTitle(): string
    {
        return 'Nuevo usuario';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Usuario creado')
            ->body("Entréguele a {$this->record->name} su correo y contraseña para el primer ingreso.");
    }
}
