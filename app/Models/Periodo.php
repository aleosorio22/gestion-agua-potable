<?php

namespace App\Models;

use App\Models\Concerns\EsCatalogo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Ciclo mensual de facturación. Una vez cerrado no admite lecturas nuevas.
 */
class Periodo extends Model implements Auditable
{
    use EsCatalogo;
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'periodos';

    protected $fillable = [
        'anio',
        'mes',
        'fecha_inicio',
        'fecha_fin',
        'cerrado_en',
        'cerrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'cerrado_en' => 'datetime',
        ];
    }

    /**
     * Un período que ya recibió lecturas o emitió boletas no se borra. Uno
     * vacío sí: `periodos` no tiene baja lógica, así que el mes abierto por
     * equivocación se elimina de verdad.
     *
     * @return array<string, string>
     */
    public function relacionesQueImpidenBorrado(): array
    {
        return [
            'lecturas' => 'lectura|lecturas',
            'boletas' => 'boleta|boletas',
        ];
    }

    /**
     * Los datos del período que toca abrir: el mes siguiente al último
     * registrado, o el mes en curso si todavía no hay ninguno.
     *
     * Evita que el rango de fechas se teclee a mano, que es de donde salen los
     * períodos que no calzan con el mes que dicen cubrir.
     *
     * @return array{anio: int, mes: int, fecha_inicio: string, fecha_fin: string}
     */
    public static function siguienteSugerido(): array
    {
        $ultimo = static::query()
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->first();

        $inicio = $ultimo
            ? Carbon::create($ultimo->anio, $ultimo->mes, 1)->addMonth()
            : now()->startOfMonth();

        return [
            'anio' => $inicio->year,
            'mes' => $inicio->month,
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_fin' => $inicio->copy()->endOfMonth()->toDateString(),
        ];
    }

    public function lecturas()
    {
        return $this->hasMany(Lectura::class);
    }

    public function boletas()
    {
        return $this->hasMany(Boleta::class);
    }

    public function cerradoPor()
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    public function getEstaCerradoAttribute(): bool
    {
        return $this->cerrado_en !== null;
    }

    public function getEtiquetaAttribute(): string
    {
        return sprintf('%04d-%02d', $this->anio, $this->mes);
    }

    public function scopeAbiertos($query)
    {
        return $query->whereNull('cerrado_en');
    }

    /**
     * El período sobre el que se trabaja hoy: el abierto que contiene la fecha
     * de hoy y, si no hay ninguno, el abierto más reciente.
     *
     * Tomar solo el más reciente deja al lector parado dentro de un mes abierto
     * por adelantado, donde la visita de hoy ni siquiera cae en el rango.
     */
    public static function vigente(): ?self
    {
        $hoy = now()->toDateString();

        return static::abiertos()
            ->orderByRaw('(fecha_inicio <= ? and fecha_fin >= ?) desc', [$hoy, $hoy])
            ->orderByDesc('fecha_inicio')
            ->first();
    }

    public function cerrar(User $usuario): void
    {
        $this->update([
            'cerrado_en' => now(),
            'cerrado_por' => $usuario->id,
        ]);
    }
}
