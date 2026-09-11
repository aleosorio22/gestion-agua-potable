<?php

namespace App\Services;

use App\Models\Boleta;
use App\Models\Lectura;
use App\Support\AjustesDeImpresion;
use Filament\Notifications\Notification;
use RuntimeException;

/**
 * Emite la boleta apenas se registra la lectura, si la oficina lo pidió.
 *
 * Nunca hace fallar el registro de la lectura. La lectura es trabajo de campo
 * ya hecho —alguien caminó hasta esa casa— y la emisión puede fallar por cosas
 * que el lector no puede resolver desde el celular: que no haya tarifa vigente
 * para esa paja, o que la serie de boletas no esté configurada. Perder la
 * medición por eso sería el peor resultado posible.
 */
class EmisorAutomatico
{
    public function __construct(
        private readonly AjustesDeImpresion $ajustes,
        private readonly EmisorBoletas $emisor,
    ) {}

    /**
     * @return Boleta|null La boleta emitida, o null si no correspondía o no se pudo.
     */
    public function emitirSiCorresponde(Lectura $lectura): ?Boleta
    {
        if (! $this->ajustes->emiteBoletaAlRegistrarLectura()) {
            return null;
        }

        try {
            $boleta = $this->emisor->emitir($lectura);
        } catch (RuntimeException $error) {
            // La lectura ya quedó guardada; esto solo avisa que su boleta no.
            Notification::make()
                ->warning()
                ->title('La lectura se guardó, pero no se pudo emitir su boleta')
                ->body($error->getMessage().' Puede emitirla desde el listado de Lecturas cuando esté resuelto.')
                ->persistent()
                ->send();

            return null;
        }

        Notification::make()
            ->success()
            ->title("Boleta {$boleta->folio} emitida")
            ->body('Total a cobrar: Q'.number_format((float) $boleta->monto, 2).'.')
            ->send();

        return $boleta;
    }
}
