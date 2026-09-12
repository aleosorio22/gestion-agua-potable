<?php

namespace App\Models;

use App\Models\Concerns\EsCatalogo;
use Carbon\Carbon;
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

    /** Serie de la que sale el código sugerido al dar de alta. */
    public const CLAVE_CORRELATIVO = 'cliente';

    protected $table = 'clientes';

    protected $fillable = [
        'codigo',
        'token_alta',
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
     * Agrega a cada cliente lo que debe y cuándo venció lo más viejo.
     *
     * El saldo de una boleta se deriva de sus pagos y no es columna, así que
     * calcularlo en PHP costaría una consulta por cliente y otra por boleta
     * —con quinientos vecinos eso se siente—. Estas subconsultas lo resuelven
     * de una sola pasada, dentro del motor.
     *
     * `deuda` suma todas las boletas vigentes: las saldadas aportan cero, así
     * que el total es exactamente lo que falta cobrarle.
     */
    public function scopeConEstadoDeCuenta(Builder $query): Builder
    {
        $pagado = '(select coalesce(sum(p.monto), 0) from pagos p '
            .'where p.boleta_id = boletas.id and p.revertido_en is null)';

        return $query
            ->addSelect([
                'deuda' => Boleta::query()
                    ->selectRaw("coalesce(sum(boletas.monto - {$pagado}), 0)")
                    ->whereColumn('boletas.cliente_id', 'clientes.id')
                    ->whereNull('boletas.anulada_en'),

                // La más vieja que todavía no se termina de pagar: es la que
                // dice si el vecino está simplemente pendiente o ya vencido.
                'vence_mas_antigua' => Boleta::query()
                    ->selectRaw('min(boletas.fecha_vencimiento)')
                    ->whereColumn('boletas.cliente_id', 'clientes.id')
                    ->whereNull('boletas.anulada_en')
                    ->whereRaw("boletas.monto > {$pagado}"),
            ]);
    }

    /**
     * Lo que este cliente debe, según lo que trajo el estado de cuenta.
     */
    public function getDeudaTotalAttribute(): float
    {
        return round((float) ($this->attributes['deuda'] ?? 0), 2);
    }

    /**
     * Al día, pendiente o vencido. Se deriva, no se guarda.
     */
    public function getEstadoDeCuentaAttribute(): string
    {
        if ($this->deuda_total <= 0) {
            return 'al_dia';
        }

        $vence = $this->attributes['vence_mas_antigua'] ?? null;

        return $vence !== null && Carbon::parse($vence)->isPast()
            ? 'vencido'
            : 'pendiente';
    }

    /**
     * Solo los clientes con el servicio vigente.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', 'activo');
    }
}
