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
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'cliente_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

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

    // El cliente al que pertenece este login (solo aplica al rol Cliente)
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
