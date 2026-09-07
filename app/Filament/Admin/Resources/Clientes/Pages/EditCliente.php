<?php

namespace App\Filament\Admin\Resources\Clientes\Pages;

use App\Filament\Admin\Resources\Clientes\ClienteResource;
use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;

    public function getTitle(): string
    {
        return 'Editar cliente';
    }

    /**
     * No hay borrado definitivo: un cliente que ya tiene contadores o boletas
     * solo se pasa a inactivo, y el que se eliminó por error se restaura.
     */
    protected function getHeaderActions(): array
    {
        return [
            AccionesCatalogo::eliminar(),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Cambios guardados');
    }
}
