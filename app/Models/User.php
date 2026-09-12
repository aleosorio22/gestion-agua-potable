<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\EsCatalogo;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'activo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements Auditable, FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use EsCatalogo;

    use HasFactory, HasRoles, Notifiable;
    use \OwenIt\Auditing\Auditable;

    /**
     * Nunca registrar credenciales en la bitácora.
     *
     * @var array<int, string>
     */
    /**
     * Una cuenta nace activa.
     *
     * El default vive también acá y no solo en la base: una instancia recién
     * creada no relee la fila, así que sin esto `$usuario->activo` es null
     * hasta el primer refresh y cualquier chequeo de baja da falso negativo.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'activo' => true,
    ];

    protected $auditExclude = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /**
     * Quién puede entrar a cada panel.
     *
     * Distingue por $panel->getId(): un rol de staff no basta para entrar al
     * portal, y el rol Cliente no basta para entrar a /admin. Sin este
     * chequeo por panel, cualquiera con acceso a un panel podía colarse al
     * otro, que es justo la separación que el portal necesita garantizar.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Antes que cualquier rol: una cuenta dada de baja no entra a ningún
        // panel, por más permisos que conserve.
        if (! $this->activo) {
            return false;
        }

        if ($panel->getId() === 'portal') {
            return $this->hasRole('Cliente') && $this->clienteAcceso()->exists();
        }

        $rolesConAcceso = match ($panel->getId()) {
            'lector' => config('lector.panel_roles', []),
            default => config('admin.panel_roles', []),
        };

        return $this->hasAnyRole([
            config('filament-shield.super_admin.name', 'super_admin'),
            ...$rolesConAcceso,
        ]);
    }

    /**
     * Trabajo hecho por esta cuenta que impide borrarla.
     *
     * No es una formalidad: las cuatro son `restrictOnDelete` en la base.
     * Borrar al lector borraría de quién fue cada medición.
     *
     * @return array<string, string>
     */
    public function relacionesQueImpidenBorrado(): array
    {
        return [
            'lecturas' => 'lectura tomada|lecturas tomadas',
            'pagos' => 'pago registrado|pagos registrados',
            'documentosSubidos' => 'documento subido|documentos subidos',
            'evidenciasSubidas' => 'evidencia subida|evidencias subidas',
        ];
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Los sectores que este lector tiene asignados para recorrer.
     *
     * Sin ninguno ve todo el padrón: es el caso de la oficina de un solo
     * lector, que no tiene nada que repartir.
     */
    public function sectores(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'lector_sectores')->withTimestamps();
    }

    /**
     * Si su ruta está acotada a ciertos sectores.
     */
    public function tieneRutaAsignada(): bool
    {
        return $this->sectores()->exists();
    }

    public function lecturas()
    {
        return $this->hasMany(Lectura::class, 'usuario_id');
    }

    public function documentosSubidos()
    {
        return $this->hasMany(Documento::class, 'subido_por');
    }

    public function evidenciasSubidas()
    {
        return $this->hasMany(EvidenciaLectura::class, 'subido_por');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'usuario_id');
    }

    /**
     * El acceso ACTIVO de este usuario al portal, si tiene uno. Un usuario
     * de staff normalmente no tiene ninguno.
     */
    public function clienteAcceso()
    {
        return $this->hasOne(ClienteAcceso::class)->whereNull('revocado_en');
    }

    /**
     * Atajo para llegar directo al Cliente que este usuario puede consultar
     * en el portal, sin pasar por clienteAcceso() cada vez.
     */
    public function cliente(): ?Cliente
    {
        return $this->clienteAcceso?->cliente;
    }
}
