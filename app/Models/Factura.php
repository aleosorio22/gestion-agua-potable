<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Factura extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'facturas';

    protected $fillable = [
        'cliente_id',
        'lectura_id',
        'tarifa_id',
        'periodo',
        'consumo_m3',
        'monto',
        'fecha_emision',
        'fecha_vencimiento',
        'estado',
        'impresa_en',
    ];

    protected function casts(): array
    {
        return [
            'consumo_m3' => 'decimal:2',
            'monto' => 'decimal:2',
            'fecha_emision' => 'date',
            'fecha_vencimiento' => 'date',
            'impresa_en' => 'datetime',
        ];
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

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }
}
