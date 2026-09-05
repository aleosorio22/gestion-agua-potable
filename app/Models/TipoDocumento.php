<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class TipoDocumento extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'tipos_documento';

    protected $fillable = [
        'codigo',
        'nombre',
        'respalda_predio',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'respalda_predio' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }
}
