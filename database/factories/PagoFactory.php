<?php

namespace Database\Factories;

use App\Models\Factura;
use App\Models\Pago;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    protected $model = Pago::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'factura_id' => Factura::factory(),
            'usuario_id' => User::factory(),
            'monto' => fake()->randomFloat(2, 50, 300),
            'fecha_pago' => now()->toDateString(),
            'metodo_pago' => fake()->randomElement(['efectivo', 'tarjeta', 'transferencia']),
        ];
    }
}
