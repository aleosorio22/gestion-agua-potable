<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements Auditable, FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    use \OwenIt\Auditing\Auditable;

    /**
     * Nunca registrar credenciales en la bitácora.
     *
     * @var array<int, string>
     */
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
        if ($panel->getId() === 'portal') {
            return $this->hasRole('Cliente') && $this->clienteAcceso()->exists();
        }

        $rolesConAcceso = array_merge(
            [config('filament-shield.super_admin.name', 'super_admin')],
            config('admin.panel_roles', []),
        );

        return $this->hasAnyRole($rolesConAcceso);
    }

    public function lecturas()
    {
        return $this->hasMany(Lectura::class, 'usuario_id');
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
