<?php

namespace App\Models;

use App\Models\Concerns\EsCatalogo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class TipoDocumento extends Model implements Auditable
{
    use EsCatalogo;
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

    /**
     * @return array<string, string>
     */
    public function relacionesQueImpidenBorrado(): array
    {
        return [
            'documentos' => 'documento|documentos',
        ];
    }
}
