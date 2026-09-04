<?php

namespace Database\Seeders;

use App\Models\Periodo;
use Illuminate\Database\Seeder;

class PeriodoSeeder extends Seeder
{
    /**
     * Abre el período del mes en curso para que el sistema sea usable apenas
     * se instala.
     */
    public function run(): void
    {
        $inicio = now()->startOfMonth();

        Periodo::firstOrCreate(
            ['anio' => $inicio->year, 'mes' => $inicio->month],
            [
                'fecha_inicio' => $inicio->toDateString(),
                'fecha_fin' => $inicio->copy()->endOfMonth()->toDateString(),
            ]
        );
    }
}
