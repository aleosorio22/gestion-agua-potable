<?php

namespace Database\Factories;

use App\Models\Boleta;
use App\Models\Cliente;
use App\Models\Lectura;
use App\Models\Periodo;
use App\Models\SerieDocumento;
use App\Models\Tarifa;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Boleta> */
class BoletaFactory extends Factory
{
    protected $model = Boleta::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $numero = fake()->unique()->numberBetween(1, 999999);
        $ejercicio = (int) now()->year;

        return [
            // Una sola serie activa por tipo, como en producción: abrir una
            // por boleta chocaría con SerieDocumentoObserver.
            'serie_id' => fn (): int => SerieDocumento::activaPara('boleta')?->id
                ?? SerieDocumento::factory()->create()->id,
            'ejercicio' => $ejercicio,
            'numero' => $numero,
            'folio' => 'BOL-'.$ejercicio.str_pad((string) $numero, 6, '0', STR_PAD_LEFT),
            'cliente_id' => Cliente::factory(),
            'lectura_id' => Lectura::factory(),
            'tarifa_id' => Tarifa::factory(),
            'periodo_id' => Periodo::factory(),
            'consumo_m3' => 10.00,
            'monto_base' => 50.00,
            'monto_excedente' => 0,
            'monto' => 50.00,
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
        ];
    }

    public function anulada(): static
    {
        return $this->state(fn (array $attributes): array => [
            'anulada_en' => now(),
            'motivo_anulacion' => 'Emitida por error',
        ]);
    }

    public function vencida(): static
    {
        return $this->state(fn (array $attributes): array => [
            'fecha_vencimiento' => now()->subDay()->toDateString(),
        ]);
    }
}
