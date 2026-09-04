<?php

namespace Database\Factories;

use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sector> */
class SectorFactory extends Factory
{
    protected $model = Sector::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->streetName(),
            'descripcion' => null,
            'orden' => fake()->numberBetween(1, 20),
            'activo' => true,
        ];
    }
}
