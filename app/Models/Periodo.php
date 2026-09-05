<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Ciclo mensual de facturación. Una vez cerrado no admite lecturas nuevas.
 */
class Periodo extends Model implements Auditable
{
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

    public function cerrar(User $usuario): void
    {
        $this->update([
            'cerrado_en' => now(),
            'cerrado_por' => $usuario->id,
        ]);
    }
}
