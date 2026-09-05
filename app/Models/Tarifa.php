<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Tarifa por paja, versionada por fecha de inicio.
 *
 * No existe `vigente_hasta` en la tabla: una tarifa rige desde su fecha hasta
 * que empieza la siguiente. El rango completo se consulta en la vista
 * `tarifas_vigencia` o con el accessor `vigente_hasta` de este modelo.
 */
class Tarifa extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'tarifas';

    protected $fillable = [
        'paja_id',
        'monto_base',
        'precio_m3_excedente',
        'vigente_desde',
    ];

    protected function casts(): array
    {
        return [
            'monto_base' => 'decimal:2',
            'precio_m3_excedente' => 'decimal:4',
            'vigente_desde' => 'date',
        ];
    }

    public function paja()
    {
        return $this->belongsTo(Paja::class);
    }

    public function boletas()
    {
        return $this->hasMany(Boleta::class);
    }

    /**
     * La tarifa aplicable a una paja en una fecha: la más reciente de las que
     * ya habían empezado.
     *
     * Devuelve exactamente una fila o ninguna, nunca dos, así que no hay
     * solapamiento posible. Cero filas significa que la fecha es anterior a la
     * primera tarifa registrada de esa paja.
     */
    public static function vigenteEn(int $pajaId, $fecha): ?self
    {
        return static::query()
            ->where('paja_id', $pajaId)
            ->whereDate('vigente_desde', '<=', $fecha)
            ->orderByDesc('vigente_desde')
            ->first();
    }

    public function scopeVigentesEn($query, $fecha)
    {
        return $query->whereDate('vigente_desde', '<=', $fecha);
    }

    /**
     * Fin de vigencia derivado: el día anterior al inicio de la siguiente
     * tarifa de la misma paja. NULL significa que es la vigente hoy.
     */
    public function getVigenteHastaAttribute(): ?Carbon
    {
        $siguiente = static::query()
            ->where('paja_id', $this->paja_id)
            ->where('vigente_desde', '>', $this->vigente_desde)
            ->orderBy('vigente_desde')
            ->value('vigente_desde');

        return $siguiente ? Carbon::parse($siguiente)->subDay() : null;
    }

    public function getEsVigenteAttribute(): bool
    {
        return $this->vigente_hasta === null;
    }

    /**
     * Monto que corresponde a un consumo bajo esta tarifa.
     *
     * Cuota fija hasta la equivalencia contratada; el excedente se cobra por m3.
     */
    public function calcularMonto(float $consumoM3, float $equivalenciaM3): array
    {
        $excedenteM3 = max(0, $consumoM3 - $equivalenciaM3);

        $montoBase = (float) $this->monto_base;
        $montoExcedente = round($excedenteM3 * (float) $this->precio_m3_excedente, 2);

        return [
            'monto_base' => $montoBase,
            'monto_excedente' => $montoExcedente,
            'monto' => round($montoBase + $montoExcedente, 2),
        ];
    }
}
