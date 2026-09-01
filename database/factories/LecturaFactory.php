<?php

namespace Database\Factories;

use App\Models\Contador;
use App\Models\Lectura;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lectura>
 */
class LecturaFactory extends Factory
{
    protected $model = Lectura::class;

    /**
     * consumo_m3 no se define aquí: es una columna generada (STORED) que
     * calcula la base de datos a partir de las dos lecturas.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $anterior = fake()->randomFloat(2, 0, 500);

        return [
            'contador_id' => Contador::factory(),
            'usuario_id' => User::factory(),
            'periodo' => now()->format('Y-m'),
            'lectura_anterior' => $anterior,
            'lectura_actual' => $anterior + fake()->randomFloat(2, 1, 50),
            'fecha_lectura' => now()->toDateString(),
        ];
    }
}
