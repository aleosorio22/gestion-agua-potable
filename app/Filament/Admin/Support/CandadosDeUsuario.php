<?php

namespace App\Filament\Admin\Support;

use App\Models\User;

/**
 * Lo que nadie puede hacer con las cuentas, por más permisos que tenga.
 *
 * No son reglas de negocio: son los frenos que evitan que la oficina quede
 * encerrada afuera de su propio sistema. Un administrador que se desactiva por
 * error, o que da de baja al último que podía administrar, deja el panel sin
 * nadie capaz de revertirlo — y eso solo se arregla desde la base de datos.
 */
class CandadosDeUsuario
{
    /**
     * Motivo por el que esta cuenta no se puede desactivar, o null si se puede.
     */
    public static function motivoParaNoDesactivar(User $usuario): ?string
    {
        if (! $usuario->activo) {
            return null;
        }

        if (static::esUnoMismo($usuario)) {
            return 'No puede desactivar su propia cuenta: quedaría fuera del sistema sin poder revertirlo.';
        }

        if (static::esElUltimoAdministrador($usuario)) {
            return 'Es el único administrador activo. Active a otro antes de dar de baja a este, o el sistema queda sin quién lo administre.';
        }

        return null;
    }

    /**
     * Motivo por el que esta cuenta no se puede eliminar, o null si se puede.
     */
    public static function motivoParaNoEliminar(User $usuario): ?string
    {
        if (static::esUnoMismo($usuario)) {
            return 'No puede eliminar su propia cuenta.';
        }

        if ($motivo = $usuario->motivoDeUso()) {
            return "Registró {$motivo}. Desactívelo en lugar de eliminarlo: borrarlo borraría de quién fue ese trabajo.";
        }

        if (static::esElUltimoAdministrador($usuario)) {
            return 'Es el único administrador activo.';
        }

        return null;
    }

    public static function esUnoMismo(User $usuario): bool
    {
        return $usuario->getKey() === auth()->id();
    }

    /**
     * El último con el rol que administra el sistema, contando solo activos.
     */
    public static function esElUltimoAdministrador(User $usuario): bool
    {
        $rol = config('filament-shield.super_admin.name', 'super_admin');

        if (! $usuario->hasRole($rol)) {
            return false;
        }

        return User::query()
            ->activos()
            ->whereKeyNot($usuario->getKey())
            ->whereHas('roles', fn ($consulta) => $consulta->where('name', $rol))
            ->doesntExist();
    }
}
