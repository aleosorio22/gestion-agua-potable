<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Respaldo escaneado del expediente.
 *
 * `predio_id` nulo = documento de la persona (DPI, NIT).
 * `predio_id` con valor = documento que respalda esa propiedad concreta
 * (recibo de luz, escritura, contrato de servicio).
 */
class Documento extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'documentos';

    protected $fillable = [
        'cliente_id',
        'predio_id',
        'tipo_documento_id',
        'disco',
        'ruta',
        'nombre_original',
        'mime',
        'tamano_bytes',
        'hash_sha256',
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

    public function predio()
    {
        return $this->belongsTo(Predio::class);
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    public function subidoPor()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function scopeDePredio($query, int $predioId)
    {
        return $query->where('predio_id', $predioId);
    }

    public function scopeDeLaPersona($query)
    {
        return $query->whereNull('predio_id');
    }
}
