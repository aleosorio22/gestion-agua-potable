<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Cliente extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'codigo',
        'nombre',
        'nit',
        'dpi',
        'telefono',
        'email',
        'direccion_notificacion',
        'estado',
    ];

    public function contadores()
    {
        return $this->hasMany(Contador::class);
    }

    public function boletas()
    {
        return $this->hasMany(Boleta::class);
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }

    /**
     * Los predios donde este cliente tiene servicio, vía sus contadores.
     */
    public function predios()
    {
        return $this->hasManyThrough(
            Predio::class,
            Contador::class,
            'cliente_id',
            'id',
            'id',
            'predio_id'
        );
    }

    /**
     * Scope a query to only include active clients.
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }
}
