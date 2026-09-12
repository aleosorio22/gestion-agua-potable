<?php

namespace App\Http\Middleware;

use App\Services\Instalador;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mientras el sistema no esté instalado, todo lleva al instalador.
 *
 * Sin esto, quien abre la aplicación recién subida al servidor se topa con un
 * error de conexión a base de datos, que no dice qué hacer.
 */
class RedirigirSiNoEstaInstalado
{
    public function __construct(private readonly Instalador $instalador) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->instalador->estaInstalado() || $request->routeIs('instalador.*')) {
            return $next($request);
        }

        return redirect()->route('instalador.bienvenida');
    }
}
