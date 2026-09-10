<?php

namespace App\Filament\Admin\Resources\Periodos\Pages;

use App\Filament\Admin\Resources\Periodos\PeriodoResource;
use App\Filament\Admin\Support\AccionCerrarPeriodo;
use App\Filament\Admin\Support\AccionesCatalogo;
use App\Models\Periodo;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPeriodo extends EditRecord
{
    protected static string $resource = PeriodoResource::class;

    public function getTitle(): string
    {
        return "Período {$this->record->etiqueta}";
    }

    protected function getHeaderActions(): array
    {
        return [
            AccionCerrarPeriodo::make(),
            AccionesCatalogo::eliminar()
                ->hidden(fn (Periodo $record): bool => $record->esta_cerrado),
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
