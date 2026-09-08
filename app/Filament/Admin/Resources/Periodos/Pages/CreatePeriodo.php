<?php

namespace App\Filament\Admin\Resources\Periodos\Pages;

use App\Filament\Admin\Resources\Periodos\PeriodoResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePeriodo extends CreateRecord
{
    protected static string $resource = PeriodoResource::class;

    public function getTitle(): string
    {
        return 'Abrir período';
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title("Período {$this->record->etiqueta} abierto")
            ->body('El lector ya puede registrar lecturas de este mes.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
