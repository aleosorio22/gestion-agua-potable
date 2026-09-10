<?php

namespace App\Filament\Admin\Resources\Contadores\Pages;

use App\Filament\Admin\Resources\Contadores\ContadorResource;
use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditContador extends EditRecord
{
    protected static string $resource = ContadorResource::class;

    public function getTitle(): string
    {
        return 'Editar contador';
    }

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
