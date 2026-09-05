<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Documento de cobro que emite la oficina.
 *
 * No tiene columna `estado`: pagada, pendiente y vencida se derivan de los
 * pagos y de la fecha, así que no pueden desincronizarse. Solo la anulación
 * es un hecho que se registra, con autor y motivo.
 */
class Boleta extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'boletas';

    protected $fillable = [
        'serie_id',
        'ejercicio',
        'numero',
        'folio',
        'cliente_id',
        'lectura_id',
        'tarifa_id',
        'periodo_id',
        'consumo_m3',
        'monto_base',
        'monto_excedente',
        'monto',
        'fecha_emision',
        'fecha_vencimiento',
        'impresa_en',
        'anulada_en',
        'anulada_por',
        'motivo_anulacion',
    ];

    protected function casts(): array
    {
        return [
            'consumo_m3' => 'decimal:2',
            'monto_base' => 'decimal:2',
            'monto_excedente' => 'decimal:2',
            'monto' => 'decimal:2',
            'fecha_emision' => 'date',
            'fecha_vencimiento' => 'date',
            'impresa_en' => 'datetime',
            'anulada_en' => 'datetime',
        ];
    }

    public function serie()
    {
        return $this->belongsTo(SerieDocumento::class, 'serie_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function lectura()
    {
        return $this->belongsTo(Lectura::class);
    }

    // Snapshot de qué tarifa aplicó — nunca se recalcula desde la tarifa actual
    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class);
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function anuladaPor()
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    /**
     * Pagos que cuentan para el saldo: los que no fueron revertidos.
     */
    public function pagosVigentes()
    {
        return $this->pagos()->whereNull('revertido_en');
    }

    // --- Estado derivado ---

    public function getTotalPagadoAttribute(): float
    {
        return (float) $this->pagosVigentes()->sum('monto');
    }

    public function getSaldoAttribute(): float
    {
        return round((float) $this->monto - $this->total_pagado, 2);
    }

    public function getEstaAnuladaAttribute(): bool
    {
        return $this->anulada_en !== null;
    }

    public function getEstaPagadaAttribute(): bool
    {
        return ! $this->esta_anulada && $this->saldo <= 0;
    }

    public function getEstaVencidaAttribute(): bool
    {
        return ! $this->esta_anulada
            && ! $this->esta_pagada
            && $this->fecha_vencimiento->isPast();
    }

    /**
     * Estado legible para la UI. Se calcula, no se almacena.
     */
    public function getEstadoAttribute(): string
    {
        return match (true) {
            $this->esta_anulada => 'anulada',
            $this->esta_pagada => 'pagada',
            $this->esta_vencida => 'vencida',
            default => 'pendiente',
        };
    }

    // --- Scopes ---

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereNull('anulada_en');
    }

    public function scopeAnuladas(Builder $query): Builder
    {
        return $query->whereNotNull('anulada_en');
    }

    /**
     * Boletas sin cubrir del todo. Es la consulta del dashboard de cobranza.
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->vigentes()->whereRaw(
            '(SELECT COALESCE(SUM(p.monto), 0) FROM pagos p '
            .'WHERE p.boleta_id = boletas.id AND p.revertido_en IS NULL) < boletas.monto'
        );
    }

    public function scopeVencidas(Builder $query): Builder
    {
        return $query->pendientes()->whereDate('fecha_vencimiento', '<', now());
    }

    public function scopePagadas(Builder $query): Builder
    {
        return $query->vigentes()->whereRaw(
            '(SELECT COALESCE(SUM(p.monto), 0) FROM pagos p '
            .'WHERE p.boleta_id = boletas.id AND p.revertido_en IS NULL) >= boletas.monto'
        );
    }

    public function anular(User $usuario, string $motivo): void
    {
        $this->update([
            'anulada_en' => now(),
            'anulada_por' => $usuario->id,
            'motivo_anulacion' => $motivo,
        ]);
    }
}
