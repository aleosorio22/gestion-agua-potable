<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('admin.roles', []) as $rol) {
            Role::firstOrCreate([
                'name' => $rol,
                'guard_name' => 'web',
            ]);
        }
    }
}
