<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Lectura;
use App\Models\Tarifa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Factura>
 */
class FacturaFactory extends Factory
{
    protected $model = Factura::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'lectura_id' => Lectura::factory(),
            'tarifa_id' => Tarifa::factory(),
            'periodo' => now()->format('Y-m'),
            'consumo_m3' => fake()->randomFloat(2, 1, 50),
            'monto' => fake()->randomFloat(2, 50, 300),
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'estado' => 'pendiente',
        ];
    }

    public function pagada(): static
    {
        return $this->state(fn (array $attributes): array => ['estado' => 'pagada']);
    }
}
