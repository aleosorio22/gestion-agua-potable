<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cerradura de la API de integración.
 *
 * Nginx ya impide alcanzarla desde fuera de la red privada; esto es la segunda
 * capa, para que una regla mal puesta en el servidor no deje la API abierta.
 */
class TokenIntegracion
{
    public function handle(Request $request, Closure $next): Response
    {
        $esperado = (string) config('integracion.token');
        $recibido = (string) $request->header('X-Integracion-Token');

        // Un token vacío en la configuración no autoriza a nadie: sin esta
        // guarda, un `.env` sin la variable dejaría pasar peticiones que
        // tampoco la traen.
        if ($esperado === '' || ! hash_equals($esperado, $recibido)) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }

        return $next($request);
    }
}
