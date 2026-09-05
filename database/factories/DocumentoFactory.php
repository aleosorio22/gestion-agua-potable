<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Documento;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Documento> */
class DocumentoFactory extends Factory
{
    protected $model = Documento::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'predio_id' => null,
            'tipo_documento_id' => TipoDocumento::factory(),
            'disco' => 'public',
            'ruta' => 'documentos/'.fake()->uuid().'.pdf',
            'nombre_original' => fake()->word().'.pdf',
            'mime' => 'application/pdf',
            'tamano_bytes' => fake()->numberBetween(1000, 500000),
            'hash_sha256' => hash('sha256', fake()->uuid()),
            'firmado' => false,
            'fecha_firma' => null,
            'subido_por' => User::factory(),
        ];
    }
}
