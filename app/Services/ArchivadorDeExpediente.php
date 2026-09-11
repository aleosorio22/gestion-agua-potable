<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Documento;

/**
 * Archiva el adjunto que se capturó junto con el alta.
 *
 * Existe para que el alta guiada y la conexión de un servicio no repitan la
 * misma decisión: si vino archivo se archiva, si no vino no pasa nada. Los
 * metadatos —mime, tamaño, hash— los completa `DocumentoObserver`.
 */
class ArchivadorDeExpediente
{
    /**
     * @param  array<string, mixed>  $datos  Lo que trae el bloque de adjunto del formulario.
     */
    public function adjuntar(Cliente $cliente, ?array $datos, ?int $predioId = null): ?Documento
    {
        $ruta = $datos['ruta'] ?? null;
        $tipo = $datos['tipo_documento_id'] ?? null;

        // Sin archivo no hay nada que archivar: el adjunto es opcional y la
        // mayoría de las altas van a pasar por acá sin traer nada.
        if (blank($ruta) || blank($tipo)) {
            return null;
        }

        return Documento::create([
            'cliente_id' => $cliente->getKey(),
            'predio_id' => $predioId,
            'tipo_documento_id' => $tipo,
            'disco' => 'local',
            'ruta' => is_array($ruta) ? reset($ruta) : $ruta,
            'nombre_original' => $datos['nombre_original'] ?? basename(is_array($ruta) ? reset($ruta) : $ruta),
            'mime' => 'application/octet-stream',
            'tamano_bytes' => 0,
            'hash_sha256' => '',
            'firmado' => false,
        ]);
    }
}
