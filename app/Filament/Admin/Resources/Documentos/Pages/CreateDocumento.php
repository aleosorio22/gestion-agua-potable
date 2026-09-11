<?php

namespace App\Filament\Admin\Resources\Documentos\Pages;

use App\Filament\Admin\Resources\Documentos\DocumentoResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumento extends CreateRecord
{
    protected static string $resource = DocumentoResource::class;

    public function getTitle(): string
    {
        return 'Cargar documento';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Documento cargado')
            ->body("Queda en el expediente de {$this->record->cliente->nombre}.");
    }
}
