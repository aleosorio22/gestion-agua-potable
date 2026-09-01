<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Tarifa extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'tarifas';

    protected $fillable = [
        'paja_id',
        'monto_base',
        'precio_m3_excedente',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected function casts(): array
    {
        return [
            'monto_base' => 'decimal:2',
            'precio_m3_excedente' => 'decimal:4',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function paja()
    {
        return $this->belongsTo(Paja::class);
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }

    // Tarifa vigente de una paja en una fecha dada — la usará el Service/Observer
    // que calcula el monto al guardar una lectura.
    public function scopeVigenteEn($query, string $paja_id, $fecha)
    {
        return $query->where('paja_id', $paja_id)
            ->where('vigente_desde', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->whereNull('vigente_hasta')
                  ->orWhere('vigente_hasta', '>', $fecha);
            });
    }
}
