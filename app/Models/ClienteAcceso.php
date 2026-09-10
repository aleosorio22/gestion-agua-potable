<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Quién (User) puede consultar los datos de qué Cliente en el portal de
 * autoservicio. Reemplaza al viejo `users.cliente_id` fijo — deja rastro de
 * quién otorgó el acceso y permite revocarlo sin perder el historial.
 */
class ClienteAcceso extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'cliente_accesos';

    protected $fillable = [
        'cliente_id',
        'user_id',
        'otorgado_por',
        'otorgado_en',
        'revocado_por',
        'revocado_en',
    ];

    protected function casts(): array
    {
        return [
            'otorgado_en' => 'datetime',
            'revocado_en' => 'datetime',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function otorgadoPor()
    {
        return $this->belongsTo(User::class, 'otorgado_por');
    }

    public function revocadoPor()
    {
        return $this->belongsTo(User::class, 'revocado_por');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->whereNull('revocado_en');
    }

    public function revocar(User $usuario): void
    {
        $this->update([
            'revocado_en' => now(),
            'revocado_por' => $usuario->id,
        ]);
    }
}
