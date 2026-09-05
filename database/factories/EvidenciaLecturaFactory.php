<?php

namespace Database\Factories;

use App\Models\EvidenciaLectura;
use App\Models\Lectura;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EvidenciaLectura> */
class EvidenciaLecturaFactory extends Factory
{
    protected $model = EvidenciaLectura::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'lectura_id' => Lectura::factory(),
            'disco' => 'public',
            'ruta' => 'evidencias/'.fake()->uuid().'.jpg',
            'nombre_original' => 'foto.jpg',
            'mime' => 'image/jpeg',
            'tamano_bytes' => fake()->numberBetween(50000, 3000000),
            'hash_sha256' => hash('sha256', fake()->uuid()),
            'descripcion' => null,
            'subido_por' => User::factory(),
        ];
    }
}
