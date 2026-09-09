<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Crea un usuario de prueba con rol Lector, para que cualquiera del equipo
 * pueda probar /admin con permisos recortados sin crearlo a mano por Tinker
 * cada vez que reconstruye su base local. Depende de que RoleSeeder (rol
 * Lector) ya haya corrido antes.
 */
class LectorDemoSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::firstOrCreate(
            ['email' => config('admin.lector_demo.email')],
            [
                'name' => config('admin.lector_demo.name'),
                'password' => config('admin.lector_demo.password'), // el cast 'hashed' lo encripta solo
            ]
        );

        $usuario->assignRole('Lector');
    }
}
