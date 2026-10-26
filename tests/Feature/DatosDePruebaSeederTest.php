<?php

use App\Models\Boleta;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\ConfiguracionSeeder;
use Database\Seeders\DatosDePruebaSeeder;
use Database\Seeders\SerieDocumentoSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * El reloj se fija a un septiembre cualquiera para que el año sembrado tenga
 * nueve meses corridos: los escenarios de mora no existen si se corre en enero.
 */
beforeEach(function () {
    $this->travelTo(Carbon::create(2026, 9, 22, 8, 0));

    User::factory()->create(['email' => config('admin.email')]);

    $this->seed([
        ConfiguracionSeeder::class,
        CatalogosSeeder::class,
        SerieDocumentoSeeder::class,
    ]);
});

/** @return Collection<int, Cliente> */
function vecinosSembrados(): Collection
{
    return Cliente::query()
        ->select('clientes.*')
        ->conEstadoDeCuenta()
        ->where('codigo', 'like', 'DEMO-%')
        ->orderBy('codigo')
        ->get();
}

it('siembra el año completo con vecinos al día, pendientes y vencidos', function () {
    $this->seed(DatosDePruebaSeeder::class);

    $vecinos = vecinosSembrados();

    expect($vecinos)->toHaveCount(26)
        ->and($vecinos->map->estado_de_cuenta->unique()->values()->all())
        ->toContain('al_dia', 'pendiente', 'vencido');

    // Enero a septiembre para quien tuvo servicio todo el año.
    expect($vecinos->first()->boletas()->count())->toBe(9);
});

it('deja al menos un vecino debiendo más de dos meses', function () {
    $this->seed(DatosDePruebaSeeder::class);

    $morosos = vecinosSembrados()->filter(
        fn (Cliente $vecino): bool => $vecino->boletas()->vencidas()->count() >= 2
    );

    expect($morosos)->not->toBeEmpty();
});

it('cubre los hechos que no se ven en un padrón recién instalado', function () {
    $this->seed(DatosDePruebaSeeder::class);

    expect(Boleta::anuladas()->count())->toBeGreaterThan(0)
        ->and(Pago::revertidos()->count())->toBeGreaterThan(0)
        ->and(Boleta::where('monto_excedente', '>', 0)->count())->toBeGreaterThan(0)
        // Abonos a medias: boleta con pago pero todavía con saldo.
        ->and(Boleta::pendientes()->whereHas('pagosVigentes')->count())->toBeGreaterThan(0);
});

it('cierra los meses ya liquidados y deja abiertos los dos últimos', function () {
    $this->seed(DatosDePruebaSeeder::class);

    expect(Periodo::query()->abiertos()->pluck('mes')->all())->toBe([8, 9]);
});

it('se puede volver a correr sin duplicar el padrón', function () {
    $this->seed(DatosDePruebaSeeder::class);

    $vecinos = Cliente::query()->where('codigo', 'like', 'DEMO-%')->count();
    $boletas = Boleta::count();

    $this->seed(DatosDePruebaSeeder::class);

    expect(Cliente::query()->where('codigo', 'like', 'DEMO-%')->count())->toBe($vecinos)
        ->and(Boleta::count())->toBe($boletas);
});

it('no toca los clientes que ya estaban cargados', function () {
    $propio = Cliente::factory()->create(['codigo' => 'CLI-0001']);

    $this->seed(DatosDePruebaSeeder::class);
    $this->seed(DatosDePruebaSeeder::class);

    expect(Cliente::query()->whereKey($propio->id)->exists())->toBeTrue();
});
