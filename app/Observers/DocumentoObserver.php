<?php

namespace App\Observers;

use App\Models\Documento;
use App\Services\MetadatosDeArchivo;
use Illuminate\Support\Facades\Storage;

/**
 * Completa la ficha técnica del archivo y limpia lo que queda atrás.
 *
 * Vive en un observer y no en cada formulario porque el expediente se carga
 * desde la ficha del cliente y desde su propia pantalla, y mañana puede
 * cargarse desde una importación: el hash y el tamaño no pueden depender de
 * por dónde entró el archivo.
 */
class DocumentoObserver
{
    public function __construct(private readonly MetadatosDeArchivo $metadatos) {}

    public function creating(Documento $documento): void
    {
        $documento->disco ??= 'local';
        $documento->subido_por ??= auth()->id();

        $this->describirArchivo($documento);
    }

    public function updating(Documento $documento): void
    {
        if (! $documento->isDirty('ruta')) {
            return;
        }

        // Reemplazar el archivo sin borrar el anterior deja el viejo ocupando
        // disco para siempre, sin registro que lo nombre.
        $anterior = $documento->getOriginal('ruta');
        $discoAnterior = $documento->getOriginal('disco') ?: $documento->disco;

        if (filled($anterior)) {
            Storage::disk($discoAnterior)->delete($anterior);
        }

        $this->describirArchivo($documento);
    }

    /**
     * Solo mira el disco si el archivo está de verdad: las factories arman
     * documentos con rutas inventadas para probar relaciones, y eso no tiene
     * por qué reventar.
     */
    private function describirArchivo(Documento $documento): void
    {
        if (blank($documento->ruta) || ! Storage::disk($documento->disco)->exists($documento->ruta)) {
            return;
        }

        foreach ($this->metadatos->describir($documento->disco, $documento->ruta) as $campo => $valor) {
            $documento->{$campo} = $valor;
        }

        $documento->nombre_original ??= basename($documento->ruta);
    }
}
