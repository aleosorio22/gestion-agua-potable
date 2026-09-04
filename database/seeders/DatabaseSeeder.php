<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * El orden importa: los permisos y el rol super_admin deben existir antes
     * de sembrar el administrador, porque el seeder del admin le asigna ese rol.
     */
    public function run(): void
    {
        $this->call([
            ShieldSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
            ConfiguracionSeeder::class,
            CatalogosSeeder::class,
            SerieDocumentoSeeder::class,
            PeriodoSeeder::class,
        ]);
    }
}
