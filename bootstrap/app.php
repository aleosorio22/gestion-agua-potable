<?php

use App\Http\Middleware\RedirigirSiNoEstaInstalado;
use App\Http\Middleware\RedirigirSiYaEstaInstalado;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'instalador.pendiente' => RedirigirSiYaEstaInstalado::class,
        ]);

        // Toda la web pasa por acá: en un servidor recién montado, cualquier
        // URL lleva al instalador en vez de a un error de conexión que no dice
        // qué hacer.
        $middleware->web(append: [
            RedirigirSiNoEstaInstalado::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
