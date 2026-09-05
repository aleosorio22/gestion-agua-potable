<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Paja;
use App\Models\Predio;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Contador> */
class ContadorFactory extends Factory
{
    protected $model = Contador::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'predio_id' => Predio::factory(),
            'cliente_id' => Cliente::factory(),
            'paja_id' => Paja::factory(),
            'codigo' => fake()->unique()->bothify('CTR-#####'),
            'fecha_instalacion' => fake()->dateTimeBetween('-3 years')->format('Y-m-d'),
            'estado' => 'activo',
        ];
    }
}
