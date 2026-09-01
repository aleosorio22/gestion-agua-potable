<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Paja extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pajas';

    protected $fillable = [
        'nombre',
        'equivalencia_m3',
    ];

    protected function casts(): array
    {
        return [
            'equivalencia_m3' => 'decimal:2',
        ];
    }

    public function contadores()
    {
        return $this->hasMany(Contador::class);
    }

    public function tarifas()
    {
        return $this->hasMany(Tarifa::class);
    }
}
