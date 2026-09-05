<?php

namespace Database\Factories;

use App\Models\SerieDocumento;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SerieDocumento> */
class SerieDocumentoFactory extends Factory
{
    protected $model = SerieDocumento::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tipo_documento' => 'boleta',
            'codigo' => fake()->unique()->bothify('S##'),
            'prefijo' => 'BOL',
            'separador' => '-',
            'incluye_anio' => true,
            'longitud_numero' => 6,
            'reinicia_cada_anio' => true,
            'ejercicio' => (int) now()->year,
            'siguiente_numero' => 1,
            'activa' => true,
        ];
    }

    public function paraRecibos(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tipo_documento' => 'recibo_pago',
            'prefijo' => 'REC',
        ]);
    }
}
