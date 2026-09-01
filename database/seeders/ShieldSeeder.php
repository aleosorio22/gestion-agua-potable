<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    /**
     * Genera los permisos de Shield y arma el rol super_admin.
     *
     * Sin este paso una instalación recién clonada queda con la tabla
     * `permissions` vacía, y como las policies preguntan por permisos
     * concretos (can('ViewAny:Role')), nadie puede ver absolutamente nada.
     *
     * Se ejecuta con --option=permissions a propósito: las policies son
     * archivos PHP que se generan una vez en desarrollo y se commitean, no
     * algo que deba escribirse en disco durante un despliegue.
     */
    public function run(): void
    {
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
            '--no-interaction' => true,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $rolSuperAdmin = Role::firstOrCreate([
            'name' => config('filament-shield.super_admin.name', 'super_admin'),
            'guard_name' => 'web',
        ]);

        $rolSuperAdmin->syncPermissions(Permission::where('guard_name', 'web')->get());
    }
}
