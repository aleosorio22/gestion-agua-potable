<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Paja extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pajas';

    protected $fillable = [
        'nombre',
        'equivalencia_m3',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'equivalencia_m3' => 'decimal:2',
            'activo' => 'boolean',
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

    /**
     * La tarifa que rige en una fecha dada para esta paja.
     */
    public function tarifaVigenteEn($fecha = null): ?Tarifa
    {
        return Tarifa::vigenteEn($this->id, $fecha ?? now());
    }
}
