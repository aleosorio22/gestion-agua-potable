<?php

namespace Database\Factories;

use App\Models\Periodo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<Periodo> */
class PeriodoFactory extends Factory
{
    protected $model = Periodo::class;

    /**
     * Cada período creado avanza un mes, porque (anio, mes) es único.
     */
    private static int $desplazamiento = 0;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $inicio = Carbon::now()->startOfMonth()->addMonths(self::$desplazamiento++);

        return [
            'anio' => $inicio->year,
            'mes' => $inicio->month,
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_fin' => $inicio->copy()->endOfMonth()->toDateString(),
            'cerrado_en' => null,
            'cerrado_por' => null,
        ];
    }

    public function cerrado(): static
    {
        return $this->state(fn (array $attributes): array => ['cerrado_en' => now()]);
    }

    /**
     * Reinicia el desplazamiento. Lo llama Pest entre pruebas.
     */
    public static function reiniciarSecuencia(): void
    {
        self::$desplazamiento = 0;
    }
}
