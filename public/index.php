<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
//
// Quien clona el repositorio y abre el navegador antes de instalar las
// dependencias se topa aquí con un error de PHP en inglés que no dice qué
// hacer. Es el primer contacto con el sistema: mejor explicarlo.
if (! file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/falta-preparar.php';

    exit;
}

require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
