<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Contador extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'contadores';

    protected $fillable = [
        'cliente_id',
        'paja_id',
        'codigo',
        'ubicacion',
        'fecha_instalacion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_instalacion' => 'date',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    // La paja contratada por este medidor específico
    public function paja()
    {
        return $this->belongsTo(Paja::class);
    }

    public function lecturas()
    {
        return $this->hasMany(Lectura::class);
    }
}
