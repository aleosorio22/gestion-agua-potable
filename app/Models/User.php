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
     * Quién puede entrar al panel administrativo.
     *
     * Sin este método Filament aborta con 403 a todo el mundo en cuanto
     * APP_ENV deja de ser "local" (ver Filament\Http\Middleware\Authenticate),
     * así que es obligatorio antes de desplegar.
     */
    public function canAccessPanel(Panel $panel): bool
    {
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
}
