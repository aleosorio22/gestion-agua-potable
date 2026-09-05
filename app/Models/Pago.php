<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Cobro recibido en ventanilla.
 *
 * No se edita ni se borra: un cheque rechazado o un error de digitación se
 * resuelven revirtiendo, que deja el pago original visible y agrega el hecho
 * que lo anula. La regla se refuerza además en las policies.
 */
class Pago extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pagos';

    protected $fillable = [
        'serie_id',
        'ejercicio',
        'numero',
        'folio',
        'boleta_id',
        'metodo_pago_id',
        'usuario_id',
        'monto',
        'fecha_pago',
        'referencia',
        'revertido_en',
        'revertido_por',
        'motivo_reverso',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_pago' => 'date',
            'revertido_en' => 'datetime',
        ];
    }

    public function serie()
    {
        return $this->belongsTo(SerieDocumento::class, 'serie_id');
    }

    public function boleta()
    {
        return $this->belongsTo(Boleta::class);
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class);
    }

    // Secretaria que registró el pago
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function revertidoPor()
    {
        return $this->belongsTo(User::class, 'revertido_por');
    }

    public function getEstaRevertidoAttribute(): bool
    {
        return $this->revertido_en !== null;
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereNull('revertido_en');
    }

    public function scopeRevertidos(Builder $query): Builder
    {
        return $query->whereNotNull('revertido_en');
    }

    public function revertir(User $usuario, string $motivo): void
    {
        $this->update([
            'revertido_en' => now(),
            'revertido_por' => $usuario->id,
            'motivo_reverso' => $motivo,
        ]);
    }
}
