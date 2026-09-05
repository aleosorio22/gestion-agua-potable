<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Datos de la entidad que opera el sistema, en formato clave-valor.
 */
class Configuracion extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'configuracion';

    protected $primaryKey = 'clave';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'clave',
        'valor',
        'descripcion',
        'actualizado_por',
    ];

    public static function obtener(string $clave, ?string $porDefecto = null): ?string
    {
        return Cache::rememberForever(
            "configuracion.{$clave}",
            fn () => static::find($clave)?->valor
        ) ?? $porDefecto;
    }

    public static function guardar(string $clave, ?string $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);

        Cache::forget("configuracion.{$clave}");
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
