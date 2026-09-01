<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class DocumentoCliente extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'documentos_cliente';

    protected $fillable = [
        'cliente_id',
        'tipo',
        'archivo_url',
        'firmado',
        'fecha_firma',
        'subido_por',
    ];

    protected function casts(): array
    {
        return [
            'firmado' => 'boolean',
            'fecha_firma' => 'date',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    // Secretaria que subió el documento
    public function subidoPor()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
