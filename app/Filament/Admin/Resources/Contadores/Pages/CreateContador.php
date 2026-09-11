<?php

namespace App\Filament\Admin\Resources\Contadores\Pages;

use App\Filament\Admin\Resources\Contadores\ContadorResource;
use App\Models\Contador;
use App\Services\ArchivadorDeExpediente;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateContador extends CreateRecord
{
    protected static string $resource = ContadorResource::class;

    public function getTitle(): string
    {
        return 'Nuevo contador';
    }

    /**
     * El contador y el documento que respalda la propiedad entran juntos.
     *
     * Conectar el servicio es el momento en que la oficina tiene la escritura
     * enfrente; si el archivo se pierde porque falló el guardado del contador,
     * se pierde la única oportunidad de capturarlo.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $documento = $data['documento'] ?? null;
        unset($data['documento']);

        return DB::transaction(function () use ($data, $documento): Contador {
            $contador = Contador::create($data);

            app(ArchivadorDeExpediente::class)->adjuntar(
                $contador->cliente,
                $documento,
                $contador->predio_id,
            );

            return $contador;
        });
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
