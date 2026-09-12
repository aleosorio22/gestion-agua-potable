<?php

namespace App\Filament\Admin\Resources\Usuarios\Pages;

use App\Filament\Admin\Resources\Usuarios\UsuarioResource;
use App\Filament\Admin\Support\AccionesUsuario;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUsuario extends EditRecord
{
    protected static string $resource = UsuarioResource::class;

    public function getTitle(): string
    {
        return 'Editar usuario';
    }

    protected function getHeaderActions(): array
    {
        return [
            AccionesUsuario::restablecerContrasena(),
            AccionesUsuario::alternarActivo(),
            AccionesUsuario::eliminar(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()->success()->title('Cambios guardados');
    }
}
