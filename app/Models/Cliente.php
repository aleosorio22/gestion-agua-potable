<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Cliente extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'nit',
        'dpi',
        'direccion',
        'telefono',
        'email',
        'estado',
    ];

    public function contadores()
    {
        return $this->hasMany(Contador::class);
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoCliente::class);
    }

    // El login del portal de autoservicio (0 o 1 usuario por cliente)
    public function usuario()
    {
        return $this->hasOne(User::class);
    }
}
