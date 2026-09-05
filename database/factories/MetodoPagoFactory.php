<?php

namespace Database\Factories;

use App\Models\MetodoPago;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MetodoPago> */
class MetodoPagoFactory extends Factory
{
    protected $model = MetodoPago::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->lexify('met_????'),
            'nombre' => fake()->word(),
            'requiere_referencia' => false,
            'activo' => true,
        ];
    }
}
