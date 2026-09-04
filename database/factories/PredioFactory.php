<?php

namespace Database\Factories;

use App\Models\Predio;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Predio> */
class PredioFactory extends Factory
{
    protected $model = Predio::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'sector_id' => Sector::factory(),
            'aldea' => fake()->randomElement(['El Porvenir', 'San Antonio', 'Las Flores']),
            'zona' => (string) fake()->numberBetween(0, 5),
            'calle' => fake()->optional()->streetName(),
            'numero_casa' => fake()->numerify('#-##'),
            'referencia' => fake()->optional()->sentence(4),
            'latitud' => null,
            'longitud' => null,
        ];
    }
}
