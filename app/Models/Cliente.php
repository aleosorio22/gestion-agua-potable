<?php

namespace App\Models;

use App\Models\Concerns\EsCatalogo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Cliente extends Model implements Auditable
{
    use EsCatalogo;
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'codigo',
        'nombre',
        'nit',
        'dpi',
        'telefono',
        'email',
        'direccion_notificacion',
        'estado',
    ];

    /**
     * Un cliente que ya tiene contadores, boletas o expediente no se borra:
     * se pasa a inactivo. La baja lógica queda para el alta duplicada o
     * tecleada por error, que es lo único que no deja rastro que conservar.
     *
     * @return array<string, string>
     */
    public function relacionesQueImpidenBorrado(): array
    {
        return [
            'contadores' => 'contador|contadores',
            'boletas' => 'boleta|boletas',
            'documentos' => 'documento|documentos',
        ];
    }

    public function contadores(): HasMany
    {
        return $this->hasMany(Contador::class);
    }

    public function boletas(): HasMany
    {
        return $this->hasMany(Boleta::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }

    /**
     * Quién(es) tienen acceso de portal a este cliente — histórico completo,
     * incluye accesos ya revocados. Para el activo, usar accesoActivo().
     */
    public function accesos(): HasMany
    {
        return $this->hasMany(ClienteAcceso::class);
    }

    /**
     * El acceso vigente al portal, si alguien lo tiene otorgado.
     */
    public function accesoActivo()
    {
        return $this->hasOne(ClienteAcceso::class)->whereNull('revocado_en');
    }

    /**
     * Los predios donde este cliente tiene servicio, vía sus contadores.
     *
     * `distinct` con columna porque dos contadores en la misma propiedad la
     * repetirían, y sin nombrar la columna el `count()` la ignora.
     */
    public function predios(): HasManyThrough
    {
        return $this->hasManyThrough(
            Predio::class,
            Contador::class,
            'cliente_id',
            'id',
            'id',
            'predio_id'
        )->distinct('predios.id');
    }

    /**
     * Solo los clientes con el servicio vigente.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', 'activo');
    }
}
