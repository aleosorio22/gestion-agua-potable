<?php

namespace App\Filament\Admin\Resources\Lecturas\Pages;

use App\Filament\Admin\Resources\Lecturas\LecturaResource;
use App\Models\Lectura;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLectura extends EditRecord
{
    protected static string $resource = LecturaResource::class;

    public function getTitle(): string
    {
        return 'Corregir lectura';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn (Lectura $record): bool => $record->esta_facturada)
                ->tooltip(fn (Lectura $record): ?string => $record->esta_facturada
                    ? 'Ya tiene boleta emitida. Anule la boleta primero.'
                    : null),
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
            ->title('Lectura corregida')
            ->body("Consumo del período: {$this->record->fresh()->consumo_m3} m³.");
    }
}
