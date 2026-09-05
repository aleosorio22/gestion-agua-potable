<?php

namespace Database\Factories;

use App\Models\Paja;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Paja> */
class PajaFactory extends Factory
{
    protected $model = Paja::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->numerify('paja-###'),
            'equivalencia_m3' => 30.00,
            'activo' => true,
        ];
    }
}
