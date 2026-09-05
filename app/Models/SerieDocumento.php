<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Numeración correlativa configurable.
 *
 * El formato se arma con las piezas de la fila, así que cada entidad define su
 * nomenclatura sin tocar código:
 *
 *   prefijo 'BOL' · separador ''  · sin año · 6 dígitos  →  BOL009130
 *   prefijo 'BOL' · separador '-' · con año · 6 dígitos  →  BOL-2026003131
 */
class SerieDocumento extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'series_documento';

    protected $fillable = [
        'tipo_documento',
        'codigo',
        'prefijo',
        'separador',
        'incluye_anio',
        'longitud_numero',
        'reinicia_cada_anio',
        'ejercicio',
        'siguiente_numero',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'incluye_anio' => 'boolean',
            'reinicia_cada_anio' => 'boolean',
            'activa' => 'boolean',
        ];
    }

    public function boletas()
    {
        return $this->hasMany(Boleta::class, 'serie_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'serie_id');
    }

    /**
     * Arma el folio a partir de las piezas de configuración.
     *
     * @return string ej. 'BOL-2026003131'
     */
    public function formatearFolio(int $numero, int $ejercicio): string
    {
        // El separador va una sola vez, entre el prefijo y el resto:
        //   'BOL' + '-' + '2026' + '003131'  →  BOL-2026003131
        $correlativo = str_pad((string) $numero, $this->longitud_numero, '0', STR_PAD_LEFT);

        if ($this->incluye_anio) {
            $correlativo = $ejercicio.$correlativo;
        }

        return $this->prefijo === ''
            ? $correlativo
            : $this->prefijo.$this->separador.$correlativo;
    }

    /**
     * Reserva el siguiente correlativo con bloqueo de fila.
     *
     * Debe llamarse DENTRO de la transacción que inserta el documento: así el
     * número se consume solo si el documento llega a existir, y dos usuarios
     * emitiendo a la vez no obtienen el mismo. El UNIQUE de la tabla destino
     * es la red de seguridad.
     *
     * @return array{numero: int, ejercicio: int, folio: string}
     */
    public function reservarNumero(): array
    {
        if (! DB::transactionLevel()) {
            throw new \RuntimeException(
                'reservarNumero() debe ejecutarse dentro de una transacción; '
                .'de lo contrario el correlativo puede quedar con huecos.'
            );
        }

        /** @var self $serie */
        $serie = static::query()->lockForUpdate()->findOrFail($this->getKey());

        $ejercicioActual = (int) now()->year;

        if ($serie->reinicia_cada_anio && $serie->ejercicio !== $ejercicioActual) {
            $serie->ejercicio = $ejercicioActual;
            $serie->siguiente_numero = 1;
        }

        $numero = (int) $serie->siguiente_numero;
        $ejercicio = (int) $serie->ejercicio;

        $serie->siguiente_numero = $numero + 1;
        $serie->save();

        $this->refresh();

        return [
            'numero' => $numero,
            'ejercicio' => $ejercicio,
            'folio' => $serie->formatearFolio($numero, $ejercicio),
        ];
    }

    public static function activaPara(string $tipoDocumento): ?self
    {
        return static::query()
            ->where('tipo_documento', $tipoDocumento)
            ->where('activa', true)
            ->first();
    }
}
