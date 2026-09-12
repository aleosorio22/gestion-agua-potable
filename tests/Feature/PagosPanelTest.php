<?php

use App\Filament\Admin\Resources\Boletas\BoletaResource;
use App\Filament\Admin\Resources\Pagos\PagoResource;
use App\Models\Boleta;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\SerieDocumento;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * El cobro en ventanilla.
 *
 * Lo que interesa demostrar es que las reglas de `RegistradorPagos` lleguen
 * como notificación y no como pantalla de error con el vecino enfrente, que el
 * saldo se mueva de verdad, y que un pago nunca desaparezca: se revierte.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->seed(ShieldSeeder::class);

    $this->actingAs(
        User::factory()->create()->assignRole(
            Role::findByName(config('filament-shield.super_admin.name'), 'web')
        )
    );

    SerieDocumento::factory()->paraRecibos()->create(['activa' => true]);

    $this->efectivo = MetodoPago::factory()->create([
        'nombre' => 'Efectivo',
        'requiere_referencia' => false,
    ]);

    $this->deposito = MetodoPago::factory()->create([
        'nombre' => 'Depósito bancario',
        'requiere_referencia' => true,
    ]);
});

function boletaDe(float $monto): Boleta
{
    return Boleta::factory()->create(['monto' => $monto]);
}

function cobrar(Boleta $boleta, array $datos): Testable
{
    return Livewire::test(BoletaResource::getPages()['index']->getPage())
        ->callTableAction('cobrar', $boleta, data: $datos);
}

it('pinta el listado de pagos', function () {
    Pago::factory()->count(3)->create();

    Livewire::test(PagoResource::getPages()['index']->getPage())->assertSuccessful();
});

it('no deja crear pagos a mano', function () {
    expect(PagoResource::canCreate())->toBeFalse();
});

it('cobra una boleta y la deja saldada', function () {
    $boleta = boletaDe(64.00);

    cobrar($boleta, [
        'metodo_pago_id' => $this->efectivo->id,
        'monto' => 64.00,
        'fecha_pago' => now()->toDateString(),
    ])->assertHasNoTableActionErrors();

    $pago = Pago::first();
    $boleta->refresh();

    expect((float) $pago->monto)->toBe(64.0)
        ->and($pago->usuario_id)->toBe(auth()->id())
        ->and($pago->folio)->toStartWith('REC')
        ->and($boleta->saldo)->toBe(0.0)
        ->and($boleta->esta_pagada)->toBeTrue();
});

it('acepta un abono parcial y deja el resto pendiente', function () {
    $boleta = boletaDe(100.00);

    cobrar($boleta, [
        'metodo_pago_id' => $this->efectivo->id,
        'monto' => 40.00,
        'fecha_pago' => now()->toDateString(),
    ])->assertHasNoTableActionErrors();

    $boleta->refresh();

    expect($boleta->saldo)->toBe(60.0)
        ->and($boleta->esta_pagada)->toBeFalse();
});

it('rechaza un cobro mayor que el saldo pendiente', function () {
    $boleta = boletaDe(50.00);

    cobrar($boleta, [
        'metodo_pago_id' => $this->efectivo->id,
        'monto' => 80.00,
        'fecha_pago' => now()->toDateString(),
    ])->assertHasTableActionErrors(['monto']);

    expect(Pago::count())->toBe(0);
});

it('exige la referencia en los metodos que la necesitan', function () {
    $boleta = boletaDe(50.00);

    cobrar($boleta, [
        'metodo_pago_id' => $this->deposito->id,
        'monto' => 50.00,
        'fecha_pago' => now()->toDateString(),
    ])->assertHasTableActionErrors(['referencia']);

    expect(Pago::count())->toBe(0);
});

it('guarda la referencia del deposito', function () {
    $boleta = boletaDe(50.00);

    cobrar($boleta, [
        'metodo_pago_id' => $this->deposito->id,
        'monto' => 50.00,
        'referencia' => 'BOL-99887',
        'fecha_pago' => now()->toDateString(),
    ])->assertHasNoTableActionErrors();

    expect(Pago::first()->referencia)->toBe('BOL-99887');
});

it('rechaza una fecha de pago futura', function () {
    $boleta = boletaDe(50.00);

    cobrar($boleta, [
        'metodo_pago_id' => $this->efectivo->id,
        'monto' => 50.00,
        'fecha_pago' => now()->addWeek()->toDateString(),
    ])->assertHasTableActionErrors(['fecha_pago']);
});

it('no ofrece cobrar una boleta ya saldada ni una anulada', function () {
    $saldada = boletaDe(50.00);
    Pago::factory()->create(['boleta_id' => $saldada->id, 'monto' => 50.00]);

    $anulada = boletaDe(50.00);
    $anulada->anular(auth()->user(), 'Lectura mal tomada');

    Livewire::test(BoletaResource::getPages()['index']->getPage())
        ->assertTableActionHidden('cobrar', $saldada)
        ->assertTableActionHidden('cobrar', $anulada);
});

it('avisa en vez de reventar si no hay serie de recibos configurada', function () {
    SerieDocumento::query()->where('tipo_documento', 'recibo_pago')->update(['activa' => false]);

    $boleta = boletaDe(50.00);

    cobrar($boleta, [
        'metodo_pago_id' => $this->efectivo->id,
        'monto' => 50.00,
        'fecha_pago' => now()->toDateString(),
    ])->assertSuccessful();

    expect(Pago::count())->toBe(0);
});

it('revierte un pago devolviendo el saldo a la boleta', function () {
    $boleta = boletaDe(64.00);
    $pago = Pago::factory()->create(['boleta_id' => $boleta->id, 'monto' => 64.00]);

    expect($boleta->fresh()->saldo)->toBe(0.0);

    Livewire::test(PagoResource::getPages()['index']->getPage())
        ->callTableAction('revertir', $pago, data: ['motivo' => 'Cheque rechazado']);

    $pago->refresh();

    expect($pago->esta_revertido)->toBeTrue()
        ->and($pago->motivo_reverso)->toBe('Cheque rechazado')
        ->and($pago->revertido_por)->toBe(auth()->id())
        // El pago sigue ahí: lo que cambia es que deja de contar.
        ->and(Pago::count())->toBe(1)
        ->and($boleta->fresh()->saldo)->toBe(64.0);
});

it('exige el motivo para revertir', function () {
    $pago = Pago::factory()->create();

    Livewire::test(PagoResource::getPages()['index']->getPage())
        ->callTableAction('revertir', $pago, data: ['motivo' => ''])
        ->assertHasTableActionErrors(['motivo']);

    expect($pago->fresh()->esta_revertido)->toBeFalse();
});

it('no ofrece revertir dos veces el mismo pago', function () {
    $pago = Pago::factory()->create();
    $pago->revertir(auth()->user(), 'Duplicado');

    Livewire::test(PagoResource::getPages()['index']->getPage())
        ->assertTableActionHidden('revertir', $pago);
});

it('no permite borrar un pago ni con permiso de shield', function () {
    $pago = Pago::factory()->create();

    expect(auth()->user()->can('delete', $pago))->toBeFalse()
        ->and(auth()->user()->can('forceDelete', $pago))->toBeFalse();
});

it('vuelve a dejar cobrable una boleta cuyo pago se revirtio', function () {
    $boleta = boletaDe(64.00);
    $pago = Pago::factory()->create(['boleta_id' => $boleta->id, 'monto' => 64.00]);

    $pago->revertir(auth()->user(), 'Cheque rechazado');

    Livewire::test(BoletaResource::getPages()['index']->getPage())
        ->assertTableActionVisible('cobrar', $boleta);
});

it('imprime el recibo del pago con el monto recibido', function () {
    $boleta = boletaDe(100.00);
    $pago = Pago::factory()->create(['boleta_id' => $boleta->id, 'monto' => 40.00]);

    $this->get(route('recibos.pago', $pago))
        ->assertOk()
        ->assertSee($pago->folio)
        ->assertSee($boleta->cliente->nombre)
        ->assertSee('RECIBÍ Q.40.00', escape: false)
        // Un abono parcial tiene que decirlo: si no, el vecino se va creyendo
        // que quedó al día.
        ->assertSee('Saldo pendiente: Q.60.00', escape: false);
});

it('marca el recibo como saldada cuando no queda nada por cobrar', function () {
    $boleta = boletaDe(64.00);
    $pago = Pago::factory()->create(['boleta_id' => $boleta->id, 'monto' => 64.00]);

    $this->get(route('recibos.pago', $pago))
        ->assertOk()
        ->assertSee('BOLETA SALDADA');
});

it('no entrega el recibo de un pago revertido', function () {
    $pago = Pago::factory()->create();
    $pago->revertir(auth()->user(), 'Cheque rechazado');

    $this->get(route('recibos.pago', $pago))->assertNotFound();
});

it('niega el recibo a quien no puede ver pagos', function () {
    $pago = Pago::factory()->create();

    $this->actingAs(User::factory()->create());

    $this->get(route('recibos.pago', $pago))->assertForbidden();
});
