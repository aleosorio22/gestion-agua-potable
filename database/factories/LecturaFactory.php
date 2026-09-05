<?php

namespace Database\Factories;

use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Periodo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lectura> */
class LecturaFactory extends Factory
{
    protected $model = Lectura::class;

    /**
     * consumo_m3 no se define aquí: es una columna generada (STORED) que
     * calcula la base de datos a partir de las dos lecturas.
     *
     * lectura_anterior arranca en 0 porque LecturaObserver exige que coincida
     * con la última lectura del contador, y por defecto no hay ninguna.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contador_id' => Contador::factory(),
            'periodo_id' => Periodo::factory(),
            'usuario_id' => User::factory(),
            'lectura_anterior' => 0,
            'lectura_actual' => fake()->randomFloat(2, 1, 50),
            'fecha_lectura' => now()->toDateString(),
            'observaciones' => null,
        ];
    }

    /**
     * Lectura encadenada a la anterior del mismo contador.
     */
    public function siguienteDe(Lectura $anterior): static
    {
        return $this->state(fn (array $attributes): array => [
            'contador_id' => $anterior->contador_id,
            'lectura_anterior' => $anterior->lectura_actual,
            'lectura_actual' => (float) $anterior->lectura_actual + fake()->randomFloat(2, 1, 50),
        ]);
    }
}
