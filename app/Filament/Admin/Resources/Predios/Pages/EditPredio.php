<?php

namespace App\Filament\Admin\Resources\Predios\Pages;

use App\Filament\Admin\Resources\Predios\PredioResource;
use App\Filament\Admin\Support\AccionesCatalogo;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPredio extends EditRecord
{
    protected static string $resource = PredioResource::class;

    public function getTitle(): string
    {
        return 'Editar predio';
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
