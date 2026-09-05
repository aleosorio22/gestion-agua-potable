<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Usuario administrador inicial
    |--------------------------------------------------------------------------
    |
    | Credenciales del administrador que siembra AdminUserSeeder al preparar
    | una instalación nueva. Se leen desde aquí y no con env() directo en el
    | seeder, porque en producción se ejecuta `php artisan config:cache` y a
    | partir de ese momento env() devuelve null fuera de los archivos config.
    |
    */

    'email' => env('ADMIN_EMAIL', 'admin@oficina-agua.test'),

    'password' => env('ADMIN_PASSWORD', 'cambiar-esta-clave'),

    'name' => env('ADMIN_NAME', 'Administrador'),

    /*
    |--------------------------------------------------------------------------
    | Roles de la aplicación
    |--------------------------------------------------------------------------
    |
    | Fuente única de verdad de los roles del negocio. RoleSeeder los crea a
    | partir de esta lista. El rol `super_admin` no aparece aquí porque lo
    | administra Shield (ver config/filament-shield.php).
    |
    */

    'roles' => [
        'Administrador',
        'Secretaria',
        'Lector',
        'Cliente',
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles con acceso al panel administrativo
    |--------------------------------------------------------------------------
    |
    | Quiénes pueden entrar a /admin. El rol Cliente queda fuera a propósito:
    | su acceso será el portal de autoservicio, no el panel interno.
    |
    */

    'panel_roles' => [
        'Administrador',
        'Secretaria',
        'Lector',
    ],

];
