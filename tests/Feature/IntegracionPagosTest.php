<?php

use App\Models\Boleta;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\PagoEnvio;
use App\Models\Paja;
use App\Models\Periodo;
use App\Models\SerieDocumento;
use App\Models\Tarifa;
use App\Models\User;
use App\Services\EmisorBoletas;
use App\Services\RegistradorPagos;

const TOKEN_DE_PRUEBA = 'token-de-integracion-para-pruebas';

beforeEach(function () {
    config()->set('integracion.token', TOKEN_DE_PRUEBA);
    config()->set('integracion.prefijo', 'EQA');
});

/**
 * Un cobro completo, con toda la cadena que el mensaje canónico necesita:
 * cliente, predio, contador, período, boleta y recibo.
 */
function pagoParaIntegracion(string $metodo = 'efectivo', float $monto = 65.0): Pago
{
    SerieDocumento::factory()->create(['tipo_documento' => 'boleta', 'activa' => true]);
    SerieDocumento::factory()->paraRecibos()->create(['activa' => true]);

    $paja = Paja::factory()->create(['equivalencia_m3' => 30.00]);

    Tarifa::factory()->create([
        'paja_id' => $paja->id,
        'monto_base' => 50.00,
        'precio_m3_excedente' => 1.5000,
        'vigente_desde' => now()->startOfYear()->toDateString(),
    ]);

    $contador = Contador::factory()->create(['paja_id' => $paja->id]);
    $periodo = Periodo::factory()->create();

    $lectura = Lectura::factory()->create([
        'contador_id' => $contador->id,
        'periodo_id' => $periodo->id,
        'lectura_anterior' => 0,
        'lectura_actual' => 40.0,
    ]);

    $boleta = app(EmisorBoletas::class)->emitir($lectura);

    $metodoPago = MetodoPago::factory()->create([
        'codigo' => $metodo,
        'requiere_referencia' => false,
    ]);

    return app(RegistradorPagos::class)->registrar(
        $boleta,
        $metodoPago,
        User::factory()->create(),
        $monto,
    );
}

/** @return array<string, string> */
function cabeceraDeIntegracion(): array
{
    return ['X-Integracion-Token' => TOKEN_DE_PRUEBA];
}

it('crea el envío pendiente al registrar un pago', function () {
    $pago = pagoParaIntegracion();

    expect($pago->envio)->not->toBeNull()
        ->and($pago->envio->llave)->toBe(sprintf('EQA-%06d', $pago->id))
        ->and($pago->envio->estado_envio)->toBe(PagoEnvio::PENDIENTE)
        ->and($pago->envio->estado_banco)->toBe('PENDIENTE')
        ->and($pago->envio->estado_erp)->toBe('PENDIENTE');
});

it('crea un solo envío por pago, no dos', function () {
    $pago = pagoParaIntegracion();

    // Si el observer quedara registrado dos veces, el unique de pago_id
    // reventaría al cobrar. Esta prueba es la red para ese error.
    expect(PagoEnvio::where('pago_id', $pago->id)->count())->toBe(1);
});

it('rechaza el reclamo sin token', function () {
    pagoParaIntegracion();

    $this->postJson('/api/integracion/pagos/reclamar')
        ->assertUnauthorized();
});

it('rechaza el reclamo con un token equivocado', function () {
    pagoParaIntegracion();

    $this->postJson('/api/integracion/pagos/reclamar', [], ['X-Integracion-Token' => 'otro'])
        ->assertUnauthorized();
});

it('entrega los pagos pendientes y los deja encolados', function () {
    $pago = pagoParaIntegracion();

    $respuesta = $this->postJson('/api/integracion/pagos/reclamar', [], cabeceraDeIntegracion())
        ->assertOk();

    expect($respuesta->json('data'))->toHaveCount(1);

    $envio = $pago->envio->fresh();

    expect($envio->estado_envio)->toBe(PagoEnvio::ENCOLADO)
        ->and($envio->encolado_at)->not->toBeNull()
        ->and($envio->correlation_id)->not->toBeNull();
});

it('no entrega dos veces el mismo pago', function () {
    pagoParaIntegracion();

    $primera = $this->postJson('/api/integracion/pagos/reclamar', [], cabeceraDeIntegracion());
    $segunda = $this->postJson('/api/integracion/pagos/reclamar', [], cabeceraDeIntegracion());

    expect($primera->json('data'))->toHaveCount(1)
        ->and($segunda->json('data'))->toBeEmpty();
});

it('vuelve a entregar un encolado que se quedó sin respuesta', function () {
    $pago = pagoParaIntegracion();

    $this->postJson('/api/integracion/pagos/reclamar', [], cabeceraDeIntegracion());

    // El producer se cayó entre reclamar y publicar en la cola.
    $pago->envio->update(['encolado_at' => now()->subMinutes(31)]);

    $respuesta = $this->postJson('/api/integracion/pagos/reclamar', [], cabeceraDeIntegracion());

    expect($respuesta->json('data'))->toHaveCount(1);
});

it('nunca entrega un pago revertido', function () {
    $pago = pagoParaIntegracion();
    $pago->revertir(User::factory()->create(), 'El cheque no tenía fondos.');

    $this->postJson('/api/integracion/pagos/reclamar', [], cabeceraDeIntegracion())
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('arma el mensaje canónico con los datos del vecino', function () {
    $pago = pagoParaIntegracion(monto: 65.0);
    $pago->loadMissing('boleta.cliente', 'boleta.periodo', 'boleta.lectura.contador');

    $canonico = $this->postJson('/api/integracion/pagos/reclamar', [], cabeceraDeIntegracion())
        ->assertOk()
        ->json('data.0');

    expect($canonico)->toHaveKeys([
        'llave', 'pagoId', 'folio', 'ronda', 'correlationId',
        'cliente', 'contador', 'periodo', 'formaPago',
        'fechaPago', 'fechaEmision', 'total', 'descripcion', 'detalle',
    ]);

    expect($canonico['cliente']['codigo'])->toBe($pago->boleta->cliente->codigo)
        ->and($canonico['contador'])->toBe($pago->boleta->lectura->contador->codigo)
        ->and($canonico['periodo']['anio'])->toBe($pago->boleta->periodo->anio)
        ->and($canonico['total'])->toBe('65.00')
        ->and($canonico['formaPago'])->toBe('EFECTIVO');

    // La regla que el ERP verifica: el desglose tiene que sumar el total.
    $suma = array_sum(array_map(fn (array $linea): float => (float) $linea['monto'], $canonico['detalle']));

    expect($suma)->toBe(65.0);
});

it('usa CF cuando el vecino no tiene NIT', function () {
    $pago = pagoParaIntegracion();
    $pago->boleta->cliente->update(['nit' => null]);

    $canonico = $this->postJson('/api/integracion/pagos/reclamar', [], cabeceraDeIntegracion())
        ->json('data.0');

    expect($canonico['cliente']['nit'])->toBe('CF');
});

it('manda el depósito como transferencia, que es lo que admiten los contratos', function () {
    pagoParaIntegracion(metodo: 'deposito');

    $canonico = $this->postJson('/api/integracion/pagos/reclamar', [], cabeceraDeIntegracion())
        ->json('data.0');

    expect($canonico['formaPago'])->toBe('TRANSFERENCIA');
});

it('falla fuerte si un método de pago no tiene equivalente en los contratos', function () {
    pagoParaIntegracion(metodo: 'criptomoneda');

    $this->withoutExceptionHandling()
        ->postJson('/api/integracion/pagos/reclamar', [], cabeceraDeIntegracion());
})->throws(RuntimeException::class, 'no tiene equivalente');

it('da por enviado el pago cuando los dos destinos responden bien', function () {
    $pago = pagoParaIntegracion();
    $llave = $pago->envio->llave;

    // Un resultado solo llega para algo que antes se encoló.
    $this->postJson('/api/integracion/pagos/reclamar', [], cabeceraDeIntegracion());

    $this->patchJson("/api/integracion/pagos/{$llave}/envio", [
        'destino' => 'BANCO', 'estado' => 'OK', 'ronda' => 1, 'comprobante' => 'BNK-0001',
    ], cabeceraDeIntegracion())->assertOk();

    // Con un solo destino entregado todavía no se da por enviado.
    expect($pago->envio->fresh()->estado_envio)->toBe(PagoEnvio::ENCOLADO);

    $this->patchJson("/api/integracion/pagos/{$llave}/envio", [
        'destino' => 'ERP', 'estado' => 'OK', 'ronda' => 1, 'partida' => 'PDA-2026-1',
    ], cabeceraDeIntegracion())->assertOk();

    $envio = $pago->envio->fresh();

    expect($envio->estado_envio)->toBe(PagoEnvio::ENVIADO)
        ->and($envio->enviado_at)->not->toBeNull()
        ->and($envio->comprobante_banco)->toBe('BNK-0001')
        ->and($envio->partida_erp)->toBe('PDA-2026-1');
});

it('marca error si un destino falla, aunque el otro haya entregado', function () {
    $pago = pagoParaIntegracion(metodo: 'cheque');
    $llave = $pago->envio->llave;

    // El caso real: el banco no admite cheques por este canal.
    $this->patchJson("/api/integracion/pagos/{$llave}/envio", [
        'destino' => 'BANCO', 'estado' => 'ERROR', 'ronda' => 1, 'error' => 'UNSUPPORTED_CHANNEL',
    ], cabeceraDeIntegracion())->assertOk();

    $this->patchJson("/api/integracion/pagos/{$llave}/envio", [
        'destino' => 'ERP', 'estado' => 'OK', 'ronda' => 1,
    ], cabeceraDeIntegracion())->assertOk();

    $envio = $pago->envio->fresh();

    expect($envio->estado_envio)->toBe(PagoEnvio::ERROR)
        ->and($envio->estado_banco)->toBe('ERROR')
        ->and($envio->error_banco)->toBe('UNSUPPORTED_CHANNEL')
        ->and($envio->estado_erp)->toBe('OK');
});

it('repetir el mismo resultado no cambia nada', function () {
    $pago = pagoParaIntegracion();
    $llave = $pago->envio->llave;

    $cuerpo = ['destino' => 'BANCO', 'estado' => 'OK', 'ronda' => 1, 'comprobante' => 'BNK-0001'];

    $this->patchJson("/api/integracion/pagos/{$llave}/envio", $cuerpo, cabeceraDeIntegracion());
    $this->patchJson("/api/integracion/pagos/{$llave}/envio", $cuerpo, cabeceraDeIntegracion())->assertOk();

    expect($pago->envio->fresh()->comprobante_banco)->toBe('BNK-0001')
        ->and($pago->envio->fresh()->estado_banco)->toBe('OK');
});

it('no degrada a error un destino que ya estaba entregado', function () {
    $pago = pagoParaIntegracion();
    $llave = $pago->envio->llave;

    $this->patchJson("/api/integracion/pagos/{$llave}/envio", [
        'destino' => 'BANCO', 'estado' => 'OK', 'ronda' => 1, 'comprobante' => 'BNK-0001',
    ], cabeceraDeIntegracion());

    // Un reintento tardío del mismo pago no puede borrar la entrega.
    $this->patchJson("/api/integracion/pagos/{$llave}/envio", [
        'destino' => 'BANCO', 'estado' => 'ERROR', 'ronda' => 1, 'error' => 'TIMEOUT',
    ], cabeceraDeIntegracion())->assertOk();

    expect($pago->envio->fresh()->estado_banco)->toBe('OK')
        ->and($pago->envio->fresh()->error_banco)->toBeNull();
});

it('ignora el resultado de una ronda anterior', function () {
    $pago = pagoParaIntegracion();
    $llave = $pago->envio->llave;

    $pago->envio->update(['ronda' => 2]);

    $this->patchJson("/api/integracion/pagos/{$llave}/envio", [
        'destino' => 'BANCO', 'estado' => 'ERROR', 'ronda' => 1, 'error' => 'viejo',
    ], cabeceraDeIntegracion())
        ->assertOk()
        ->assertJsonStructure(['ignorado']);

    expect($pago->envio->fresh()->estado_banco)->toBe('PENDIENTE');
});

it('el backfill le crea su envío a los pagos que ya existían', function () {
    $pago = pagoParaIntegracion();
    $pago->envio->delete();

    $this->artisan('integracion:backfill')->assertSuccessful();

    expect($pago->fresh()->envio)->not->toBeNull()
        ->and($pago->fresh()->envio->llave)->toBe(sprintf('EQA-%06d', $pago->id));
});

it('el backfill deja fuera a los vecinos de prueba', function () {
    $pago = pagoParaIntegracion();
    $pago->envio->delete();
    $pago->boleta->cliente->update(['codigo' => 'DEMO-0001']);

    $this->artisan('integracion:backfill')->assertSuccessful();

    expect($pago->fresh()->envio)->toBeNull();
});
