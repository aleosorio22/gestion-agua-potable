<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Estado del envío de un pago al banco y al ERP municipal.
 *
 * No es auditable a propósito, a diferencia del resto del dominio: cambia con
 * cada intento de entrega y llenaría la bitácora de ruido técnico. El rastro
 * fino de cada intento vive en DynamoDB, que es para lo que está.
 */
class PagoEnvio extends Model
{
    protected $table = 'pago_envios';

    /** Un envío que todavía no sale hacia los destinos. */
    public const PENDIENTE = 'PENDIENTE';

    /** Reclamado por el producer, a la espera del resultado. */
    public const ENCOLADO = 'ENCOLADO';

    /** Entregado en los dos destinos. */
    public const ENVIADO = 'ENVIADO';

    /** Al menos un destino terminó en error definitivo. */
    public const ERROR = 'ERROR';

    protected $fillable = [
        'pago_id',
        'llave',
        'ronda',
        'estado_envio',
        'estado_banco',
        'estado_erp',
        'comprobante_banco',
        'comprobante_erp',
        'partida_erp',
        'error_banco',
        'error_erp',
        'correlation_id',
        'encolado_at',
        'enviado_at',
    ];

    protected function casts(): array
    {
        return [
            'ronda' => 'integer',
            'encolado_at' => 'datetime',
            'enviado_at' => 'datetime',
        ];
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    /**
     * La llave con la que este pago se identifica ante el banco y el ERP.
     *
     * Sale del id y no del folio del recibo porque los contratos piden un
     * número estable que no cambie nunca; el folio sí puede reformatearse
     * desde la configuración de series.
     */
    public static function llaveDe(Pago $pago): string
    {
        return sprintf('%s-%06d', config('integracion.prefijo'), $pago->id);
    }

    /**
     * Lo que al producer le toca llevarse: lo pendiente, más lo que quedó
     * encolado sin respuesta más allá del tiempo de gracia.
     */
    public function scopeReclamables(Builder $query, int $minutos): Builder
    {
        return $query
            ->where(fn (Builder $envio) => $envio
                ->where('estado_envio', self::PENDIENTE)
                ->orWhere(fn (Builder $atascado) => $atascado
                    ->where('estado_envio', self::ENCOLADO)
                    ->where('encolado_at', '<', now()->subMinutes($minutos))))
            // Un pago revertido ya no debe salir hacia ningún destino.
            ->whereHas('pago', fn ($pago) => $pago->whereNull('revertido_en'));
    }
}
