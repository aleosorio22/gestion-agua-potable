<?php

namespace Database\Factories;

use App\Models\TipoDocumento;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TipoDocumento> */
class TipoDocumentoFactory extends Factory
{
    protected $model = TipoDocumento::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->lexify('tipo_????'),
            'nombre' => fake()->words(2, true),
            'respalda_predio' => false,
            'activo' => true,
        ];
    }

    public function dePredio(): static
    {
        return $this->state(fn (array $attributes): array => ['respalda_predio' => true]);
    }
}
