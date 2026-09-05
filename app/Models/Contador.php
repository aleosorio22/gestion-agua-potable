<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * El medidor físico instalado en un predio.
 */
class Contador extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $table = 'contadores';

    protected $fillable = [
        'predio_id',
        'cliente_id',
        'paja_id',
        'codigo',
        'fecha_instalacion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_instalacion' => 'date',
        ];
    }

    // Dónde está instalado
    public function predio()
    {
        return $this->belongsTo(Predio::class);
    }

    // Quién es el titular del servicio
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function paja()
    {
        return $this->belongsTo(Paja::class);
    }

    public function lecturas()
    {
        return $this->hasMany(Lectura::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Contadores activos que aún no tienen lectura en el período dado.
     * Es la consulta principal de la pantalla del lector.
     */
    public function scopeSinLecturaEn($query, int $periodoId)
    {
        return $query->activos()->whereDoesntHave(
            'lecturas',
            fn ($q) => $q->where('periodo_id', $periodoId)
        );
    }

    /**
     * La última lectura registrada, para precargar `lectura_anterior`.
     */
    public function ultimaLectura(): ?Lectura
    {
        return $this->lecturas()->orderByDesc('fecha_lectura')->orderByDesc('id')->first();
    }
}
