<?php

namespace App\Filament\Admin\Resources\Predios\Pages;

use App\Filament\Admin\Resources\Predios\PredioResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePredio extends CreateRecord
{
    protected static string $resource = PredioResource::class;

    public function getTitle(): string
    {
        return 'Nuevo predio';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Predio registrado')
            ->body('Ya puede instalarle un contador.');
    }
}
