<?php

namespace Database\Factories;

use App\Models\Paja;
use App\Models\Tarifa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tarifa>
 */
class TarifaFactory extends Factory
{
    protected $model = Tarifa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paja_id' => Paja::factory(),
            'monto_base' => 50.00,
            'precio_m3_excedente' => 1.5000,
            'vigente_desde' => now()->startOfYear()->toDateString(),
            'vigente_hasta' => null,
        ];
    }

    /**
     * Tarifa histórica, ya cerrada.
     */
    public function cerrada(): static
    {
        return $this->state(fn (array $attributes): array => [
            'vigente_hasta' => now()->subMonth()->toDateString(),
        ]);
    }
}
