<?php

use App\Filament\Admin\Widgets\EstadoDeCuentaClientes;
use App\Filament\Admin\Widgets\ResumenCobranza;
use App\Filament\Admin\Widgets\TrabajoPendiente;
use App\Models\Boleta;
use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * El tablero de la oficina: quién debe y cuánto.
 *
 * El saldo se deriva de los pagos y no es columna, así que lo que más importa
 * demostrar es que la suma sea correcta y que no se resuelva a fuerza de una
 * consulta por cliente.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->seed(ShieldSeeder::class);

    $this->actingAs(
        User::factory()->create()->assignRole(
            Role::findByName(config('filament-shield.super_admin.name'), 'web')
        )
    );
});

/** Una boleta de un cliente concreto, con vencimiento a elección. */
function boletaDelCliente(Cliente $cliente, float $monto, string $vence): Boleta
{
    return Boleta::factory()->create([
        'cliente_id' => $cliente->id,
        'monto' => $monto,
        'fecha_vencimiento' => $vence,
    ]);
}

it('pinta el tablero con sus tres bloques', function () {
    Cliente::factory()->count(3)->create();

    Livewire::test(ResumenCobranza::class)->assertSuccessful();
    Livewire::test(TrabajoPendiente::class)->assertSuccessful();
    Livewire::test(EstadoDeCuentaClientes::class)->assertSuccessful();
});

it('suma la deuda de todos los servicios del cliente', function () {
    $cliente = Cliente::factory()->create();

    boletaDelCliente($cliente, 64.00, now()->addWeek()->toDateString());
    boletaDelCliente($cliente, 40.00, now()->addWeek()->toDateString());

    $conEstado = Cliente::conEstadoDeCuenta()->find($cliente->id);

    expect($conEstado->deuda_total)->toBe(104.0)
        ->and($conEstado->estado_de_cuenta)->toBe('pendiente');
});

it('descuenta los pagos y deja al cliente al dia', function () {
    $cliente = Cliente::factory()->create();
    $boleta = boletaDelCliente($cliente, 64.00, now()->addWeek()->toDateString());

    Pago::factory()->create(['boleta_id' => $boleta->id, 'monto' => 64.00]);

    $conEstado = Cliente::conEstadoDeCuenta()->find($cliente->id);

    expect($conEstado->deuda_total)->toBe(0.0)
        ->and($conEstado->estado_de_cuenta)->toBe('al_dia');
});

it('cuenta como deuda un abono parcial', function () {
    $cliente = Cliente::factory()->create();
    $boleta = boletaDelCliente($cliente, 100.00, now()->addWeek()->toDateString());

    Pago::factory()->create(['boleta_id' => $boleta->id, 'monto' => 40.00]);

    expect(Cliente::conEstadoDeCuenta()->find($cliente->id)->deuda_total)->toBe(60.0);
});

it('vuelve a contar la deuda de un pago revertido', function () {
    $cliente = Cliente::factory()->create();
    $boleta = boletaDelCliente($cliente, 64.00, now()->addWeek()->toDateString());
    $pago = Pago::factory()->create(['boleta_id' => $boleta->id, 'monto' => 64.00]);

    expect(Cliente::conEstadoDeCuenta()->find($cliente->id)->deuda_total)->toBe(0.0);

    $pago->revertir(auth()->user(), 'Cheque rechazado');

    expect(Cliente::conEstadoDeCuenta()->find($cliente->id)->deuda_total)->toBe(64.0);
});

it('no cuenta la deuda de una boleta anulada', function () {
    $cliente = Cliente::factory()->create();
    $boleta = boletaDelCliente($cliente, 64.00, now()->addWeek()->toDateString());

    $boleta->anular(auth()->user(), 'Lectura mal tomada');

    expect(Cliente::conEstadoDeCuenta()->find($cliente->id)->deuda_total)->toBe(0.0);
});

it('marca como vencido al que paso su fecha de pago', function () {
    $cliente = Cliente::factory()->create();
    boletaDelCliente($cliente, 64.00, now()->subWeek()->toDateString());

    $conEstado = Cliente::conEstadoDeCuenta()->find($cliente->id);

    expect($conEstado->estado_de_cuenta)->toBe('vencido')
        ->and($conEstado->vence_mas_antigua)->not->toBeNull();
});

it('señala la boleta mas vieja sin pagar y no la mas nueva', function () {
    $cliente = Cliente::factory()->create();

    boletaDelCliente($cliente, 40.00, '2026-03-15');
    boletaDelCliente($cliente, 40.00, '2026-07-15');

    expect(Cliente::conEstadoDeCuenta()->find($cliente->id)->vence_mas_antigua)
        ->toStartWith('2026-03-15');
});

it('filtra el estado de cuenta por su estado', function () {
    $vencido = Cliente::factory()->create();
    boletaDelCliente($vencido, 50.00, now()->subWeek()->toDateString());

    $pendiente = Cliente::factory()->create();
    boletaDelCliente($pendiente, 50.00, now()->addWeek()->toDateString());

    $alDia = Cliente::factory()->create();
    $saldada = boletaDelCliente($alDia, 50.00, now()->addWeek()->toDateString());
    Pago::factory()->create(['boleta_id' => $saldada->id, 'monto' => 50.00]);

    Livewire::test(EstadoDeCuentaClientes::class)
        ->filterTable('estado_de_cuenta', 'vencido')
        ->assertCanSeeTableRecords([$vencido])
        ->assertCanNotSeeTableRecords([$pendiente, $alDia])
        ->filterTable('estado_de_cuenta', 'pendiente')
        ->assertCanSeeTableRecords([$pendiente])
        ->assertCanNotSeeTableRecords([$vencido, $alDia])
        ->filterTable('estado_de_cuenta', 'al_dia')
        ->assertCanSeeTableRecords([$alDia])
        ->assertCanNotSeeTableRecords([$vencido, $pendiente]);
});

it('encuentra al vecino por lo que trae a mano', function () {
    $buscado = Cliente::factory()->create([
        'nombre' => 'María Xicay',
        'dpi' => '1234567890101',
    ]);
    $otro = Cliente::factory()->create(['nombre' => 'Otro vecino']);

    Livewire::test(EstadoDeCuentaClientes::class)
        ->searchTable('1234567890101')
        ->assertCanSeeTableRecords([$buscado])
        ->assertCanNotSeeTableRecords([$otro]);
});

it('resuelve el estado de cuenta sin una consulta por cliente', function () {
    Cliente::factory()->count(20)->create()->each(
        fn (Cliente $cliente) => boletaDelCliente($cliente, 50.00, now()->addWeek()->toDateString())
    );

    DB::enableQueryLog();

    $clientes = Cliente::conEstadoDeCuenta()->withCount('contadores')->get();
    $clientes->each(fn (Cliente $cliente) => $cliente->estado_de_cuenta);

    $consultas = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Son más de veinte: cada boleta arrastra su propio cliente por la cadena
    // lectura → contador. Lo que importa es que resolverlos cueste una sola
    // consulta — calculando el saldo en PHP serían tantas como clientes.
    expect($clientes->count())->toBeGreaterThanOrEqual(20)
        ->and($consultas)->toBe(1);
});

it('suma lo cobrado del mes sin contar los reversos', function () {
    // El total de la boleta se elige para que el saldo por cobrar (Q880.00) no
    // se confunda con la suma equivocada que incluiría el reverso (Q80.00).
    $boleta = Boleta::factory()->create(['monto' => 1000.00]);

    Pago::factory()->create([
        'boleta_id' => $boleta->id,
        'monto' => 50.00,
        'fecha_pago' => now()->toDateString(),
    ]);

    $revertido = Pago::factory()->create([
        'boleta_id' => $boleta->id,
        'monto' => 30.00,
        'fecha_pago' => now()->toDateString(),
    ]);
    $revertido->revertir(auth()->user(), 'Cheque rechazado');

    Pago::factory()->create([
        'boleta_id' => $boleta->id,
        'monto' => 70.00,
        'fecha_pago' => now()->subMonths(2)->toDateString(),
    ]);

    Livewire::test(ResumenCobranza::class)
        ->assertSee('Q50.00')
        ->assertDontSee('Q80.00');
});

it('totaliza lo que falta cobrar en toda la oficina', function () {
    $primera = Boleta::factory()->create(['monto' => 100.00]);
    Pago::factory()->create(['boleta_id' => $primera->id, 'monto' => 40.00]);

    Boleta::factory()->create(['monto' => 64.00]);

    $anulada = Boleta::factory()->create(['monto' => 500.00]);
    $anulada->anular(auth()->user(), 'Lectura mal tomada');

    expect(Boleta::saldoPendienteTotal())->toBe(124.0);
});

it('avisa de las lecturas medidas que nadie cobro', function () {
    Lectura::factory()->count(2)->create();
    Boleta::factory()->create();

    Livewire::test(TrabajoPendiente::class)
        ->assertSee('Lecturas sin facturar')
        ->assertSee('Se midieron pero no se cobraron');
});

it('avisa de los predios conectados sin documento que los respalde', function () {
    Contador::factory()->create();

    Livewire::test(TrabajoPendiente::class)
        ->assertSee('Predios sin respaldo')
        ->assertSee('Con servicio y sin escritura cargada');
});

it('muestra el avance de la ruta del periodo abierto', function () {
    $periodo = Periodo::factory()->create();
    Contador::factory()->count(3)->create();
    Lectura::factory()->create(['periodo_id' => $periodo->id]);

    Livewire::test(ResumenCobranza::class)
        ->assertSee('Ruta de '.$periodo->etiqueta)
        ->assertSee('1 de 4');
});

it('dice que falta abrir el ciclo cuando no hay periodo', function () {
    Livewire::test(ResumenCobranza::class)
        ->assertSee('Sin período')
        ->assertSee('Abra el ciclo del mes para poder leer');
});
