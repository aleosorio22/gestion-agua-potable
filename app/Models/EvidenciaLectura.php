<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class EvidenciaLectura extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'evidencias_lectura';

    protected $fillable = [
        'lectura_id',
        'disco',
        'ruta',
        'nombre_original',
        'mime',
        'tamano_bytes',
        'hash_sha256',
        'descripcion',
        'subido_por',
    ];

    public function lectura()
    {
        return $this->belongsTo(Lectura::class);
    }

    // Lector que subió la evidencia
    public function subidoPor()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
