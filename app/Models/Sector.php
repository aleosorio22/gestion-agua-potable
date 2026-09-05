<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Sector extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'sectores';

    protected $fillable = [
        'nombre',
        'descripcion',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function predios()
    {
        return $this->hasMany(Predio::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
