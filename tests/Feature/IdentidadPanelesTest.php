<?php

use App\Models\Configuracion;
use App\Support\IdentidadDeLaEntidad;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Cómo se llama la oficina en sus paneles.
 *
 * Decían «Laravel» —el nombre por defecto del framework— mientras los datos
 * reales estaban en Configuración desde el primer día, leídos solo por los
 * documentos impresos.
 */
it('pone el nombre de la oficina en los tres paneles', function (string $panel) {
    Configuracion::guardar('entidad.nombre', 'Comité de Agua El Porvenir');

    Filament::setCurrentPanel($panel);

    expect(Filament::getPanel($panel)->getBrandName())
        ->toBe('Comité de Agua El Porvenir');
})->with(['admin', 'lector', 'portal']);

it('cae en el nombre de la aplicacion si la oficina no puso el suyo', function () {
    config()->set('app.name', 'Agua Potable');

    expect(app(IdentidadDeLaEntidad::class)->nombre())->toBe('Agua Potable');
});

it('muestra el logotipo solo si la oficina pidio que se imprima', function () {
    Storage::fake('local');
    Storage::disk('local')->put('configuracion/logo.png', 'contenido-de-la-imagen');
    Configuracion::guardar('entidad.logo', 'configuracion/logo.png');

    $identidad = app(IdentidadDeLaEntidad::class);

    // Cargado pero apagado: el mismo interruptor que los documentos impresos,
    // porque dos ajustes para lo mismo terminan contradiciéndose.
    expect($identidad->logo())->toBeNull();

    Configuracion::guardar('impresion.mostrar_logo', '1');

    expect($identidad->logo())->toStartWith('data:');
});

it('no intenta mostrar un logotipo que ya no esta en disco', function () {
    Storage::fake('local');
    Configuracion::guardar('entidad.logo', 'configuracion/borrado.png');
    Configuracion::guardar('impresion.mostrar_logo', '1');

    expect(app(IdentidadDeLaEntidad::class)->logo())->toBeNull();
});

it('no estalla si la tabla de configuracion todavia no existe', function () {
    // Pasa de verdad: `php artisan migrate` sobre una base recién creada
    // levanta la aplicación entera —paneles incluidos— antes de que exista
    // la tabla, y un panel que consulte ahí rompe la instalación.
    Schema::drop('configuracion');

    $identidad = app(IdentidadDeLaEntidad::class);

    expect($identidad->nombre())->toBe(config('app.name'))
        ->and($identidad->logo())->toBeNull();
});

it('refleja en el panel el nombre apenas se cambia', function () {
    Filament::setCurrentPanel('admin');

    Configuracion::guardar('entidad.nombre', 'Primer nombre');
    expect(Filament::getPanel('admin')->getBrandName())->toBe('Primer nombre');

    // El panel se construye una vez por arranque, así que el nombre tiene que
    // resolverse al pintar y no al registrarse.
    Configuracion::guardar('entidad.nombre', 'Nombre corregido');
    expect(Filament::getPanel('admin')->getBrandName())->toBe('Nombre corregido');
});
