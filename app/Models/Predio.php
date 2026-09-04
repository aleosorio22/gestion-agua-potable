<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * La propiedad servida. Es permanente: el medidor se reemplaza, el predio no.
 */
class Predio extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $table = 'predios';

    protected $fillable = [
        'sector_id',
        'aldea',
        'zona',
        'calle',
        'numero_casa',
        'referencia',
        'latitud',
        'longitud',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
        ];
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function contadores()
    {
        return $this->hasMany(Contador::class);
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }

    /**
     * Dirección legible para la boleta impresa y los listados.
     */
    public function getDireccionCompletaAttribute(): string
    {
        // filled() y no array_filter(): la zona '0' es un valor legítimo que
        // PHP considera falsy.
        $partes = array_filter([
            $this->calle,
            filled($this->numero_casa) ? "casa {$this->numero_casa}" : null,
            filled($this->zona) ? "zona {$this->zona}" : null,
            $this->aldea,
        ], fn ($parte) => filled($parte));

        return implode(', ', $partes);
    }
}
