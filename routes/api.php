<?php

use App\Http\Controllers\IntegracionPagosController;
use App\Http\Middleware\TokenIntegracion;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API de integración
|--------------------------------------------------------------------------
|
| La usa el microservicio despachador para llevarse los pagos cobrados y
| devolver el resultado de su entrega al banco y al ERP. No es para el
| público: Nginx solo la abre dentro de la red privada, y el token de
| `TokenIntegracion` es la segunda cerradura.
|
*/

Route::prefix('integracion')
    ->middleware(TokenIntegracion::class)
    ->name('integracion.')
    ->group(function (): void {
        Route::post('pagos/reclamar', [IntegracionPagosController::class, 'reclamar'])
            ->name('pagos.reclamar');

        Route::patch('pagos/{llave}/envio', [IntegracionPagosController::class, 'actualizarEnvio'])
            ->name('pagos.envio');
    });
