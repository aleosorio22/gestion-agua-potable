<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use Illuminate\Support\Facades\Storage;

/**
 * Entrega el archivo del expediente.
 *
 * Los documentos viven en disco privado justamente para que su descarga pase
 * por acá: un DPI escaneado no puede quedar accesible por URL a quien la
 * adivine. La policy decide, y recién después se lee el archivo.
 */
class DocumentoController extends Controller
{
    public function __invoke(Documento $documento)
    {
        $this->authorize('view', $documento);

        abort_unless(
            Storage::disk($documento->disco)->exists($documento->ruta),
            404,
            'El archivo ya no está en el almacenamiento.'
        );

        return Storage::disk($documento->disco)->download(
            $documento->ruta,
            $documento->nombre_original,
        );
    }
}
