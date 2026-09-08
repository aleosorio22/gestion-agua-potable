<?php

use App\Http\Controllers\ReciboContadorController;
use Illuminate\Support\Facades\Route;

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
