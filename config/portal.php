<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Roles con acceso al portal de autoservicio
    |--------------------------------------------------------------------------
    |
    | Quiénes pueden entrar a /portal. Solo el rol Cliente, y además solo si
    | tiene un acceso activo en cliente_accesos (ver User::canAccessPanel()
    | y el modelo ClienteAcceso) — tener el rol no basta por sí solo.
    |
    */

    'panel_roles' => [
        'Cliente',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cliente y usuario de prueba
    |--------------------------------------------------------------------------
    |
    | Credenciales que siembra ClienteDemoSeeder, para que cualquiera del
    | equipo tenga un login de /portal funcionando sin armarlo a mano.
    | Igual que con el admin, se leen desde aquí (no con env() directo en el
    | seeder) porque config:cache apaga env() fuera de los archivos config.
    |
    */

    'cliente_demo' => [
        'email' => env('CLIENTE_DEMO_EMAIL', 'cliente@oficina-agua.test'),
        'password' => env('CLIENTE_DEMO_PASSWORD', 'cambiar-esta-clave'),
        'name' => env('CLIENTE_DEMO_NAME', 'Cliente de Prueba'),
    ],

];