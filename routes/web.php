<?php

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