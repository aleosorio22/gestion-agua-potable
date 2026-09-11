<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Roles con acceso a la ruta de lectura
    |--------------------------------------------------------------------------
    |
    | Quiénes pueden entrar a /lector. El Lector porque es su herramienta de
    | trabajo, y el Administrador para supervisar el recorrido y probarlo sin
    | tener que prestarse una cuenta de campo.
    |
    | La Secretaria queda fuera a propósito: su trabajo es de ventanilla y ya
    | tiene el listado de Lecturas en /admin para corregir lo que haga falta.
    |
    */

    'panel_roles' => [
        'Lector',
        'Administrador',
    ],

];
