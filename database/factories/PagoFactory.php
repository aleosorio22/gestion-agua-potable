<?php

namespace Database\Factories;

use App\Models\Boleta;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\SerieDocumento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Pago> */
class PagoFactory extends Factory
{
    protected $model = Pago::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $numero = fake()->unique()->numberBetween(1, 999999);
        $ejercicio = (int) now()->year;

        return [
            'serie_id' => SerieDocumento::factory()->paraRecibos(),
            'ejercicio' => $ejercicio,
            'numero' => $numero,
            'folio' => 'REC-'.$ejercicio.str_pad((string) $numero, 6, '0', STR_PAD_LEFT),
            'boleta_id' => Boleta::factory(),
            'metodo_pago_id' => MetodoPago::factory(),
            'usuario_id' => User::factory(),
            'monto' => 50.00,
            'fecha_pago' => now()->toDateString(),
            'referencia' => null,
        ];
    }
}
