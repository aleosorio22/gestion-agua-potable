<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'nit' => fake()->unique()->numerify('########-#'),
            'dpi' => fake()->unique()->numerify('#############'),
            'direccion' => fake()->address(),
            'telefono' => fake()->numerify('####-####'),
            'email' => fake()->unique()->safeEmail(),
            'estado' => 'activo',
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes): array => ['estado' => 'inactivo']);
    }
}
