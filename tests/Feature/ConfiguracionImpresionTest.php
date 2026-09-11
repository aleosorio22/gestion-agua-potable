<?php

use App\Enums\FormatoPapel;
use App\Filament\Admin\Pages\Configuracion as PaginaConfiguracion;
use App\Filament\Admin\Pages\RutaLectura;
use App\Filament\Admin\Resources\Lecturas\LecturaResource;
use App\Models\Boleta;
use App\Models\Configuracion;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Pago;
use App\Models\Paja;
use App\Models\Periodo;
use App\Models\SerieDocumento;
use App\Models\Tarifa;
use App\Models\User;
use App\Support\AjustesDeImpresion;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Lo que cada oficina ajusta sin tocar código: el papel, el logotipo, el pie de
 * página, cómo se llama cada concepto, y si la boleta sale sola al leer.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');

    Storage::fake('local');

    $this->seed(ShieldSeeder::class);

    $this->actingAs(
        User::factory()->create()->assignRole(
            Role::findByName(config('filament-shield.super_admin.name'), 'web')
        )
    );
});

/** Un servicio listo para emitir: paja con tarifa vigente y serie activa. */
function servicioListo(): Contador
{
    SerieDocumento::factory()->create(['tipo_documento' => 'boleta', 'activa' => true]);

    $paja = Paja::factory()->create(['equivalencia_m3' => 15.00]);

    Tarifa::factory()->create([
        'paja_id' => $paja->id,
        'monto_base' => 40.00,
        'precio_m3_excedente' => 4.0000,
        'vigente_desde' => now()->subYear()->toDateString(),
    ]);

    return Contador::factory()->create(['paja_id' => $paja->id]);
}

it('pinta la pantalla de configuracion', function () {
    Livewire::test(PaginaConfiguracion::class)->assertSuccessful();
});

it('guarda los ajustes que se escriben en la pantalla', function () {
    Livewire::test(PaginaConfiguracion::class)
        ->fillForm([
            'entidad' => ['nombre' => 'Comité de Agua El Porvenir'],
            'ubicacion' => ['municipio' => 'El Progreso', 'departamento' => 'Jutiapa'],
            'impresion' => [
                'formato' => FormatoPapel::Carta->value,
                'pie_de_pagina' => 'Conserve este comprobante.',
            ],
            'facturacion' => ['dias_vencimiento' => 15],
        ])
        ->call('guardar')
        ->assertHasNoFormErrors();

    expect(Configuracion::obtener('entidad.nombre'))->toBe('Comité de Agua El Porvenir')
        ->and(Configuracion::obtener('impresion.formato'))->toBe(FormatoPapel::Carta->value)
        ->and(Configuracion::obtener('impresion.pie_de_pagina'))->toBe('Conserve este comprobante.')
        ->and(Configuracion::obtener('facturacion.dias_vencimiento'))->toBe('15');
});

it('cambia el papel del recibo impreso', function () {
    $pago = Pago::factory()->create();

    // De fábrica sale en rollo térmico.
    $this->get(route('recibos.pago', $pago))
        ->assertOk()
        ->assertSee('size: 80mm auto', escape: false);

    Configuracion::guardar('impresion.formato', FormatoPapel::Oficio->value);

    $this->get(route('recibos.pago', $pago))
        ->assertOk()
        ->assertSee('size: 216mm 330mm', escape: false)
        ->assertDontSee('size: 80mm auto', escape: false);
});

it('usa los nombres de concepto que eligio la oficina', function () {
    $pago = Pago::factory()->create();

    $this->get(route('recibos.pago', $pago))->assertOk()->assertSee('RECIBÍ');

    Configuracion::guardar('etiqueta.recibo.recibi', 'SE RECIBIÓ LA CANTIDAD DE');
    Configuracion::guardar('etiqueta.recibo.titulo_pago', 'Comprobante de ingreso');

    $this->get(route('recibos.pago', $pago))
        ->assertOk()
        ->assertSee('SE RECIBIÓ LA CANTIDAD DE')
        ->assertSee('Comprobante de ingreso');
});

it('imprime el pie de pagina cuando se configura', function () {
    $pago = Pago::factory()->create();

    $this->get(route('recibos.pago', $pago))->assertOk()->assertDontSee('Conserve este comprobante');

    Configuracion::guardar('impresion.pie_de_pagina', 'Conserve este comprobante');

    $this->get(route('recibos.pago', $pago))->assertOk()->assertSee('Conserve este comprobante');
});

it('incrusta el logotipo solo si la oficina lo pidio', function () {
    Storage::disk('local')->put('configuracion/logo.png', 'contenido-de-la-imagen');
    Configuracion::guardar('entidad.logo', 'configuracion/logo.png');

    $ajustes = app(AjustesDeImpresion::class);

    // Cargado pero apagado: no sale.
    expect($ajustes->imprimeLogo())->toBeFalse();

    Configuracion::guardar('impresion.mostrar_logo', '1');

    expect($ajustes->imprimeLogo())->toBeTrue()
        // Incrustado y no por URL: el documento se manda a imprimir apenas
        // carga, y una imagen que todavía no llegó sale en blanco.
        ->and($ajustes->logoEnBase64())->toStartWith('data:');
});

it('no intenta imprimir un logotipo que ya no esta en disco', function () {
    Configuracion::guardar('entidad.logo', 'configuracion/borrado.png');
    Configuracion::guardar('impresion.mostrar_logo', '1');

    expect(app(AjustesDeImpresion::class)->imprimeLogo())->toBeFalse();
});

it('guarda el logotipo subido desde la pantalla', function () {
    Livewire::test(PaginaConfiguracion::class)
        ->fillForm([
            'entidad' => ['nombre' => 'Comité de Agua'],
            'impresion' => [
                'formato' => FormatoPapel::Termica80->value,
                'mostrar_logo' => true,
                'logo' => null,
            ],
            'facturacion' => ['dias_vencimiento' => 30],
        ])
        ->set('data.entidad.logo', [UploadedFile::fake()->image('logo.png')])
        ->call('guardar')
        ->assertHasNoFormErrors();

    $ruta = Configuracion::obtener('entidad.logo');

    expect($ruta)->not->toBeNull()
        ->and(Storage::disk('local')->exists($ruta))->toBeTrue();
});

it('no emite la boleta al registrar la lectura si esta apagado', function () {
    $contador = servicioListo();
    // Antes de montar la pantalla: sin período abierto, `canCreate()` la
    // rechaza y el componente ni se construye.
    $periodo = Periodo::factory()->create();

    Livewire::test(LecturaResource::getPages()['create']->getPage())
        ->fillForm([
            'periodo_id' => $periodo->id,
            'contador_id' => $contador->id,
            'fecha_lectura' => now()->toDateString(),
            'lectura_actual' => 21,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Lectura::count())->toBe(1)
        ->and(Boleta::count())->toBe(0);
});

it('emite la boleta al registrar la lectura si esta encendido', function () {
    Configuracion::guardar('facturacion.emitir_al_registrar', '1');

    $contador = servicioListo();
    // Antes de montar la pantalla: sin período abierto, `canCreate()` la
    // rechaza y el componente ni se construye.
    $periodo = Periodo::factory()->create();

    Livewire::test(LecturaResource::getPages()['create']->getPage())
        ->fillForm([
            'periodo_id' => $periodo->id,
            'contador_id' => $contador->id,
            'fecha_lectura' => now()->toDateString(),
            'lectura_actual' => 21,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $boleta = Boleta::first();

    expect($boleta)->not->toBeNull()
        ->and((float) $boleta->monto)->toBe(64.0);
});

it('guarda la lectura aunque su boleta no se pueda emitir', function () {
    Configuracion::guardar('facturacion.emitir_al_registrar', '1');

    // Sin tarifa vigente para su paja: la emisión falla, pero el trabajo de
    // campo ya está hecho y no se puede perder.
    $contador = Contador::factory()->create();
    $periodo = Periodo::factory()->create();

    Livewire::test(LecturaResource::getPages()['create']->getPage())
        ->fillForm([
            'periodo_id' => $periodo->id,
            'contador_id' => $contador->id,
            'fecha_lectura' => now()->toDateString(),
            'lectura_actual' => 21,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Lectura::count())->toBe(1)
        ->and(Boleta::count())->toBe(0);
});

it('emite la boleta desde la ruta de lectura si esta encendido', function () {
    Configuracion::guardar('facturacion.emitir_al_registrar', '1');

    $contador = servicioListo();
    Periodo::factory()->create();

    Livewire::test(RutaLectura::class)
        ->callTableAction('registrar', $contador, data: [
            'lectura_anterior' => 0,
            'lectura_actual' => 21,
        ])
        ->assertHasNoTableActionErrors();

    expect(Boleta::count())->toBe(1);
});

it('avisa cuantas lecturas del periodo quedaron sin boleta', function () {
    $contador = servicioListo();
    $periodo = Periodo::factory()->create();

    Lectura::factory()->create(['contador_id' => $contador->id, 'periodo_id' => $periodo->id]);

    $aviso = Livewire::test(LecturaResource::getPages()['index']->getPage())
        ->instance()
        ->getSubheading();

    expect($aviso)->toMatch('/1 lectura de este período todavía no tiene boleta/');
});

it('no avisa nada cuando todo el periodo esta facturado', function () {
    Boleta::factory()->create();

    $aviso = Livewire::test(LecturaResource::getPages()['index']->getPage())
        ->instance()
        ->getSubheading();

    expect($aviso)->toBeNull();
});
