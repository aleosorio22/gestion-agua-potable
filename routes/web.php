<?php

use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\InstaladorController;
use App\Http\Controllers\ReciboContadorController;
use App\Http\Controllers\ReciboPagoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PortalController;

Route::get('/', function () {
    return view('layouts.app');
});
Route::get('/tarifas', function () {
    return view('tarifas');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});
/**
 * El documento de cobro que se le entrega al vecino. Va fuera del panel porque
 * se imprime: es una página sola, sin menú ni barra lateral. Pide sesión
 * iniciada, y la policy de boletas decide quién puede verlo.
 */
Route::get('/recibos/contador/{contador}', ReciboContadorController::class)
    ->middleware(['auth'])
    ->name('recibos.contador');

/**
 * Descarga de un documento del expediente. No es una URL pública: el archivo
 * está en disco privado y esta ruta lo entrega solo a quien la policy autoriza.
 */
Route::get('/documentos/{documento}', DocumentoController::class)
    ->middleware(['auth'])
    ->name('documentos.descargar');

/**
 * El comprobante que se le entrega al vecino cuando paga. Igual que la boleta,
 * va fuera del panel porque se imprime solo, sin menú alrededor.
 */
Route::get('/recibos/pago/{pago}', ReciboPagoController::class)
    ->middleware(['auth'])
    ->name('recibos.pago');

Route::get('/informacion', [PortalController::class, 'index'])->name('informacion.index');
/*
|--------------------------------------------------------------------------
| Instalador
|--------------------------------------------------------------------------
|
| Solo responde mientras el sistema no esté instalado: una vez en marcha, el
| middleware lo cierra, porque dejarlo accesible permitiría reescribir el `.env`
| y el administrador de una oficina que ya está operando.
|
*/
Route::middleware('instalador.pendiente')
    ->prefix('instalar')
    ->name('instalador.')
    ->group(function () {
        Route::get('/', [InstaladorController::class, 'bienvenida'])->name('bienvenida');
        Route::get('/requisitos', [InstaladorController::class, 'requisitos'])->name('requisitos');
        Route::get('/base-de-datos', [InstaladorController::class, 'baseDeDatos'])->name('base-de-datos');
        Route::post('/base-de-datos', [InstaladorController::class, 'probarBaseDeDatos'])->name('base-de-datos.probar');
        Route::get('/oficina', [InstaladorController::class, 'oficina'])->name('oficina');
        Route::post('/oficina', [InstaladorController::class, 'guardarOficina'])->name('oficina.guardar');
        Route::get('/administrador', [InstaladorController::class, 'administrador'])->name('administrador');
        Route::post('/administrador', [InstaladorController::class, 'instalar'])->name('instalar');
        Route::get('/listo', [InstaladorController::class, 'listo'])->name('listo');
        Route::get('/cancelar', [InstaladorController::class, 'cancelar'])->name('cancelar');
    });
