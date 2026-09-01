<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenciaLectura extends Model
{
    protected $table = 'evidencias_lectura';

    protected $fillable = [
        'lectura_id',
        'archivo_url',
        'descripcion',
        'subido_por',
    ];

    public function lectura()
    {
        return $this->belongsTo(Lectura::class);
    }

    // Operario que subió la evidencia
    public function subidoPor()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
