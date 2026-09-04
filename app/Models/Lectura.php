<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Lectura extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'lecturas';

    protected $fillable = [
        'contador_id',
        'periodo_id',
        'usuario_id',
        'lectura_anterior',
        'lectura_actual',
        'fecha_lectura',
        'observaciones',
    ];

    // consumo_m3 es columna generada (STORED) en la BD — no va en $fillable,
    // Eloquent solo la lee, nunca la escribe.

    protected function casts(): array
    {
        return [
            'lectura_anterior' => 'decimal:2',
            'lectura_actual' => 'decimal:2',
            'consumo_m3' => 'decimal:2',
            'fecha_lectura' => 'date',
        ];
    }

    public function contador()
    {
        return $this->belongsTo(Contador::class);
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }

    // Lector que tomó la lectura
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function evidencias()
    {
        return $this->hasMany(EvidenciaLectura::class);
    }

    public function boleta()
    {
        return $this->hasOne(Boleta::class);
    }

    public function getEstaFacturadaAttribute(): bool
    {
        return $this->boleta()->exists();
    }
}
