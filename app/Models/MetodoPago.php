<?php

namespace App\Models;

use App\Models\Concerns\EsCatalogo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class MetodoPago extends Model implements Auditable
{
    use EsCatalogo;
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'metodos_pago';

    protected $fillable = [
        'codigo',
        'nombre',
        'requiere_referencia',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'requiere_referencia' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * @return array<string, string>
     */
    public function relacionesQueImpidenBorrado(): array
    {
        return [
            'pagos' => 'pago registrado|pagos registrados',
        ];
    }
}
