<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea (o actualiza) el administrador inicial y le da el rol super_admin.
     *
     * Debe ejecutarse después de ShieldSeeder, que es quien genera los
     * permisos y el rol super_admin a partir de los recursos del panel.
     */
    public function run(): void
    {
        $usuario = User::updateOrCreate(
            ['email' => config('admin.email')],
            [
                'name' => config('admin.name'),
                'password' => Hash::make(config('admin.password')),
            ]
        );

        $rolSuperAdmin = config('filament-shield.super_admin.name', 'super_admin');

        if (! $usuario->hasRole($rolSuperAdmin)) {
            $usuario->assignRole($rolSuperAdmin);
        }
    }
}
