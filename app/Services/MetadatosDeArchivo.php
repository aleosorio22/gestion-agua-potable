<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Describe un archivo ya guardado en disco.
 *
 * El hash no es un adorno: sin él no hay forma de demostrar que una escritura
 * escaneada no fue sustituida después de cargarla. Se calcula una sola vez, al
 * archivar, y después solo se compara.
 */
class MetadatosDeArchivo
{
    /**
     * @return array{mime: string, tamano_bytes: int, hash_sha256: string}
     */
    public function describir(string $disco, string $ruta): array
    {
        $almacen = Storage::disk($disco);

        if (! $almacen->exists($ruta)) {
            throw new RuntimeException("El archivo '{$ruta}' no está en el disco '{$disco}'.");
        }

        return [
            'mime' => $almacen->mimeType($ruta) ?: 'application/octet-stream',
            'tamano_bytes' => (int) $almacen->size($ruta),
            'hash_sha256' => $this->hash($disco, $ruta),
        ];
    }

    /**
     * Lee el archivo por partes: un PDF escaneado de varios megas no tiene por
     * qué entrar entero en memoria para sacarle el hash.
     */
    public function hash(string $disco, string $ruta): string
    {
        $flujo = Storage::disk($disco)->readStream($ruta);

        if ($flujo === null || $flujo === false) {
            throw new RuntimeException("No se pudo leer '{$ruta}' del disco '{$disco}'.");
        }

        $contexto = hash_init('sha256');
        hash_update_stream($contexto, $flujo);
        fclose($flujo);

        return hash_final($contexto);
    }

    /**
     * Si el archivo en disco sigue siendo el que se archivó.
     */
    public function coincide(string $disco, string $ruta, string $hashEsperado): bool
    {
        return Storage::disk($disco)->exists($ruta)
            && hash_equals($hashEsperado, $this->hash($disco, $ruta));
    }
}
