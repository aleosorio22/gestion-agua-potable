<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Pago extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pagos';

    protected $fillable = [
        'factura_id',
        'usuario_id',
        'monto',
        'fecha_pago',
        'metodo_pago',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_pago' => 'date',
        ];
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    // Secretaria que registró el pago
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
