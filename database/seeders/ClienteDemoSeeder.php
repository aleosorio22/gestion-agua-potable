<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\ClienteAcceso;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Crea un Cliente y un User de prueba con acceso ya otorgado al portal, para
 * que cualquiera del equipo pueda probar /portal sin armar los tres registros
 * a mano cada vez. Depende de que RoleSeeder (rol Cliente) y AdminUserSeeder
 * (quién "otorga" el acceso) ya hayan corrido antes.
 */
class ClienteDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cliente = Cliente::firstOrCreate(
            ['codigo' => 'CLI-DEMO'],
            [
                'nombre' => 'Cliente de Prueba',
                'dpi' => '1234567890123',
                'telefono' => '00000000',
                'email' => config('portal.cliente_demo.email'),
                'direccion_notificacion' => 'Aldea de prueba, zona 0',
                'estado' => 'activo',
            ]
        );

        $usuario = User::firstOrCreate(
            ['email' => config('portal.cliente_demo.email')],
            [
                'name' => config('portal.cliente_demo.name'),
                'password' => config('portal.cliente_demo.password'), // el cast 'hashed' lo encripta solo
            ]
        );

        $usuario->assignRole('Cliente');

        $admin = User::where('email', config('admin.email'))->first();

        ClienteAcceso::firstOrCreate(
            ['cliente_id' => $cliente->id],
            [
                'user_id' => $usuario->id,
                'otorgado_por' => $admin?->id ?? $usuario->id,
                'otorgado_en' => now(),
            ]
        );
    }
}
