<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use RuntimeException;

/**
 * Numeración correlativa de los códigos del padrón.
 *
 * A diferencia de `SerieDocumento`, que numera documentos fiscales y no admite
 * huecos, esto es un *sugeridor*: propone el siguiente código libre, la oficina
 * puede sobrescribirlo, y el contador se pone al día con lo que realmente se
 * guardó. La garantía dura de que no haya dos códigos iguales la sigue dando el
 * índice único de la tabla, no este contador.
 */
class Correlativo extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'correlativos';

    protected $fillable = [
        'clave',
        'prefijo',
        'longitud',
        'siguiente_numero',
    ];

    protected function casts(): array
    {
        return [
            'longitud' => 'integer',
            'siguiente_numero' => 'integer',
        ];
    }

    /**
     * Cuántos códigos se prueban antes de rendirse. Sin tope, una tabla con un
     * hueco enorme dejaría el formulario girando.
     */
    private const INTENTOS_MAXIMOS = 1000;

    public static function para(string $clave): self
    {
        return static::query()->where('clave', $clave)->firstOr(function () use ($clave): never {
            throw new RuntimeException("No hay un correlativo configurado con la clave '{$clave}'.");
        });
    }

    /**
     * El siguiente código libre, sin consumirlo.
     *
     * No reserva nada a propósito: reservar al pintar el formulario quema un
     * número por cada alta que se abandona, y dos pestañas abiertas mostrarían
     * el mismo. Se propone; el número se da por usado recién cuando el registro
     * existe de verdad.
     *
     * @param  class-string<Model>  $modelo  Dónde mirar si el código ya está tomado.
     */
    public static function siguienteDisponible(string $clave, string $modelo, string $columna = 'codigo'): string
    {
        $correlativo = static::para($clave);
        $numero = $correlativo->siguiente_numero;

        for ($intento = 0; $intento < self::INTENTOS_MAXIMOS; $intento++) {
            $codigo = $correlativo->formatear($numero + $intento);

            $consulta = $modelo::query();

            // El índice único no distingue eliminados: un código dado de baja
            // sigue ocupando su lugar, así que proponerlo otra vez produciría
            // un choque al guardar.
            if (in_array(SoftDeletes::class, class_uses_recursive($modelo), true)) {
                $consulta->withTrashed();
            }

            $tomado = $consulta->where($columna, $codigo)->exists();

            if (! $tomado) {
                return $codigo;
            }
        }

        throw new RuntimeException(
            "No se encontró un código libre para '{$clave}' después de ".self::INTENTOS_MAXIMOS.' intentos.'
        );
    }

    /**
     * Pone el contador al día con un código que ya se guardó.
     *
     * Sirve tanto para el código que se propuso como para uno tecleado a mano:
     * si el usuario escribió CLI-0050, el siguiente sugerido pasa a ser
     * CLI-0051 en vez de volver a proponer uno que ya está ocupado. Un código
     * que no calza con el formato —los viejos, los importados— se ignora.
     */
    public static function sincronizar(string $clave, ?string $codigo): void
    {
        if (blank($codigo)) {
            return;
        }

        $correlativo = static::query()->where('clave', $clave)->first();

        if ($correlativo === null) {
            return;
        }

        $numero = $correlativo->numeroDe($codigo);

        if ($numero === null || $numero < $correlativo->siguiente_numero) {
            return;
        }

        $correlativo->update(['siguiente_numero' => $numero + 1]);
    }

    public function formatear(int $numero): string
    {
        return $this->prefijo.str_pad((string) $numero, $this->longitud, '0', STR_PAD_LEFT);
    }

    /**
     * El número dentro de un código con este formato, o null si no calza.
     */
    public function numeroDe(string $codigo): ?int
    {
        $patron = '/^'.preg_quote($this->prefijo, '/').'(\d{'.$this->longitud.',})$/';

        return preg_match($patron, $codigo, $partes) === 1
            ? (int) $partes[1]
            : null;
    }
}
