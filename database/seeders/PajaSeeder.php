<?php

namespace Database\Seeders;

use App\Models\Paja;
use Illuminate\Database\Seeder;

class PajaSeeder extends Seeder
{
    public function run(): void
    {
        $pajas = [
            ['nombre' => '1 paja', 'equivalencia_m3' => 60000.00],
            ['nombre' => '1/2 paja', 'equivalencia_m3' => 30000.00],
            ['nombre' => '1/4 paja', 'equivalencia_m3' => 15000.00],
        ];

        foreach ($pajas as $paja) {
            Paja::firstOrCreate(['nombre' => $paja['nombre']], $paja);
        }
    }
}
