<?php

namespace App\Filament\Admin\Resources\Lecturas\Pages;

use App\Filament\Admin\Resources\Lecturas\LecturaResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLectura extends CreateRecord
{
    protected static string $resource = LecturaResource::class;

    public function getTitle(): string
    {
        return 'Registrar lectura';
    }

    /**
     * El lector es quien está tomando la lectura, no una opción a elegir.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['usuario_id'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Lectura registrada')
            ->body("Consumo del período: {$this->record->consumo_m3} m³.");
    }
}
