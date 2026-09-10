<?php

namespace App\Filament\Admin\Resources\Contadores\Pages;

use App\Filament\Admin\Resources\Contadores\ContadorResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateContador extends CreateRecord
{
    protected static string $resource = ContadorResource::class;

    public function getTitle(): string
    {
        return 'Nuevo contador';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Contador registrado')
            ->body("Ya entra en la ruta de lectura con el código {$this->record->codigo}.");
    }
}
