<?php

namespace App\Http\Middleware;

use App\Services\Instalador;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra el instalador una vez que el sistema está en marcha.
 *
 * Es la mitad que importa: dejarlo accesible permitiría reescribir el `.env` y
 * el administrador de una oficina que ya está operando.
 */
class RedirigirSiYaEstaInstalado
{
    public function __construct(private readonly Instalador $instalador) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->instalador->estaInstalado()) {
            return $next($request);
        }

        return redirect('/admin');
    }
}
