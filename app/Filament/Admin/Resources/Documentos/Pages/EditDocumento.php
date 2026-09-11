<?php

namespace App\Filament\Admin\Resources\Documentos\Pages;

use App\Filament\Admin\Resources\Documentos\DocumentoResource;
use App\Filament\Admin\Support\AccionesDocumento;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDocumento extends EditRecord
{
    protected static string $resource = DocumentoResource::class;

    public function getTitle(): string
    {
        return 'Documento del expediente';
    }

    protected function getHeaderActions(): array
    {
        return [
            AccionesDocumento::descargar(),
            AccionesDocumento::verificarIntegridad(),
            AccionesDocumento::eliminar(),
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
