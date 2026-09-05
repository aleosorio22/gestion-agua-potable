<?php

use App\Filament\Admin\Resources\MetodosPago\MetodoPagoResource;
use App\Filament\Admin\Resources\Pajas\PajaResource;
use App\Filament\Admin\Resources\Sectores\SectorResource;
use App\Filament\Admin\Resources\SeriesDocumento\SerieDocumentoResource;
use App\Filament\Admin\Resources\Tarifas\TarifaResource;
use App\Filament\Admin\Resources\TiposDocumento\TipoDocumentoResource;
use App\Models\Boleta;
use App\Models\MetodoPago;
use App\Models\Paja;
use App\Models\Sector;
use App\Models\SerieDocumento;
use App\Models\Tarifa;
use App\Models\TipoDocumento;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Humo sobre las pantallas de catálogo: que listado y formulario se pinten de
 * verdad. Es lo que atrapa una columna mal escrita o una relación que no
 * existe, cosas que las pruebas de reglas no ven porque nunca renderizan.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');

    // `super_admin` no manda por gate (define_via_gate => false): su poder son
    // los permisos que sincroniza ShieldSeeder. Sin sembrarlos, las policies
    // contestan 403 a todo.
    $this->seed(ShieldSeeder::class);

    $this->actingAs(
        User::factory()->create()->assignRole(
            Role::findByName(config('filament-shield.super_admin.name'), 'web')
        )
    );
});

it('pinta el listado de cada catalogo con datos dentro', function (string $resource, callable $sembrar) {
    $sembrar();

    Livewire::test($resource::getPages()['index']->getPage())
        ->assertSuccessful();
})->with([
    'sectores' => [SectorResource::class, fn () => Sector::factory()->count(3)->create()],
    'pajas' => [PajaResource::class, fn () => Paja::factory()->count(3)->create()],
    'tarifas' => [TarifaResource::class, fn () => Boleta::factory()->create()],
    'métodos de pago' => [MetodoPagoResource::class, fn () => MetodoPago::factory()->count(3)->create()],
    'tipos de documento' => [TipoDocumentoResource::class, fn () => TipoDocumento::factory()->count(3)->create()],
    'series' => [SerieDocumentoResource::class, fn () => SerieDocumento::factory()->create()],
]);

it('pinta el formulario de alta de cada catalogo', function (string $resource) {
    Livewire::test($resource::getPages()['create']->getPage())
        ->assertSuccessful();
})->with([
    'sectores' => SectorResource::class,
    'pajas' => PajaResource::class,
    'tarifas' => TarifaResource::class,
    'métodos de pago' => MetodoPagoResource::class,
    'tipos de documento' => TipoDocumentoResource::class,
    'series' => SerieDocumentoResource::class,
]);

it('pinta el formulario de edicion de cada catalogo', function (string $resource, callable $registro) {
    Livewire::test($resource::getPages()['edit']->getPage(), ['record' => $registro()->getKey()])
        ->assertSuccessful();
})->with([
    'sectores' => [SectorResource::class, fn () => Sector::factory()->create()],
    'pajas' => [PajaResource::class, fn () => Paja::factory()->create()],
    'tarifas' => [TarifaResource::class, fn () => Tarifa::factory()->create()],
    'métodos de pago' => [MetodoPagoResource::class, fn () => MetodoPago::factory()->create()],
    'tipos de documento' => [TipoDocumentoResource::class, fn () => TipoDocumento::factory()->create()],
    'series' => [SerieDocumentoResource::class, fn () => SerieDocumento::factory()->create()],
]);

it('guarda un sector nuevo desde el formulario', function () {
    Livewire::test(SectorResource::getPages()['create']->getPage())
        ->fillForm([
            'nombre' => 'Aldea El Porvenir',
            'orden' => 1,
            'activo' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Sector::where('nombre', 'Aldea El Porvenir')->exists())->toBeTrue();
});

it('rechaza un sector con nombre repetido', function () {
    Sector::factory()->create(['nombre' => 'Aldea El Porvenir']);

    Livewire::test(SectorResource::getPages()['create']->getPage())
        ->fillForm(['nombre' => 'Aldea El Porvenir', 'orden' => 1])
        ->call('create')
        ->assertHasFormErrors(['nombre']);
});

it('rechaza dos tarifas de la misma paja que arranquen el mismo dia', function () {
    $tarifa = Tarifa::factory()->create(['vigente_desde' => '2026-01-01']);

    Livewire::test(TarifaResource::getPages()['create']->getPage())
        ->fillForm([
            'paja_id' => $tarifa->paja_id,
            'monto_base' => 60,
            'precio_m3_excedente' => 1.5,
            'vigente_desde' => '2026-01-01',
        ])
        ->call('create')
        ->assertHasFormErrors(['vigente_desde']);
});

it('acepta una tarifa nueva de la misma paja con otra fecha de vigencia', function () {
    $tarifa = Tarifa::factory()->create(['vigente_desde' => '2026-01-01']);

    Livewire::test(TarifaResource::getPages()['create']->getPage())
        ->fillForm([
            'paja_id' => $tarifa->paja_id,
            'monto_base' => 60,
            'precio_m3_excedente' => 1.5,
            'vigente_desde' => '2026-07-01',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Tarifa::where('paja_id', $tarifa->paja_id)->count())->toBe(2);
});

it('rechaza un codigo de metodo de pago con mayusculas o espacios', function () {
    Livewire::test(MetodoPagoResource::getPages()['create']->getPage())
        ->fillForm(['codigo' => 'Deposito BG', 'nombre' => 'Depósito Banrural'])
        ->call('create')
        ->assertHasFormErrors(['codigo']);
});

it('rechaza dos series con el mismo codigo para el mismo tipo de documento', function () {
    SerieDocumento::factory()->create(['tipo_documento' => 'boleta', 'codigo' => 'A1']);

    Livewire::test(SerieDocumentoResource::getPages()['create']->getPage())
        ->fillForm([
            'tipo_documento' => 'boleta',
            'codigo' => 'A1',
            'longitud_numero' => 6,
            'ejercicio' => 2026,
            'siguiente_numero' => 1,
            'activa' => false,
        ])
        ->call('create')
        ->assertHasFormErrors(['codigo']);
});

it('acepta el mismo codigo de serie en otro tipo de documento', function () {
    SerieDocumento::factory()->create(['tipo_documento' => 'boleta', 'codigo' => 'A1']);

    Livewire::test(SerieDocumentoResource::getPages()['create']->getPage())
        ->fillForm([
            'tipo_documento' => 'recibo_pago',
            'codigo' => 'A1',
            'prefijo' => 'REC',
            'longitud_numero' => 6,
            'ejercicio' => 2026,
            'siguiente_numero' => 1,
            'activa' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(SerieDocumento::where('codigo', 'A1')->count())->toBe(2);
});

it('congela el formato en el formulario de una serie que ya emitio', function () {
    $serie = Boleta::factory()->create()->serie;

    Livewire::test(SerieDocumentoResource::getPages()['edit']->getPage(), ['record' => $serie->getKey()])
        ->assertFormFieldDisabled('prefijo')
        ->assertFormFieldDisabled('longitud_numero')
        ->assertFormFieldDisabled('siguiente_numero')
        ->assertFormFieldEnabled('activa');
});

it('deja abierto el formato de una serie que todavia no emite', function () {
    $serie = SerieDocumento::factory()->create();

    Livewire::test(SerieDocumentoResource::getPages()['edit']->getPage(), ['record' => $serie->getKey()])
        ->assertFormFieldEnabled('prefijo')
        ->assertFormFieldEnabled('longitud_numero');
});

it('congela el formulario de una tarifa que ya cobro', function () {
    $tarifa = Boleta::factory()->create()->tarifa;

    Livewire::test(TarifaResource::getPages()['edit']->getPage(), ['record' => $tarifa->getKey()])
        ->assertFormFieldDisabled('monto_base')
        ->assertFormFieldDisabled('vigente_desde');
});

it('permite arrancar una serie en el numero donde quedo el talonario de papel', function () {
    Livewire::test(SerieDocumentoResource::getPages()['create']->getPage())
        ->fillForm([
            'tipo_documento' => 'boleta',
            'codigo' => 'A1',
            'prefijo' => 'BOL',
            'separador' => '',
            'incluye_anio' => false,
            'longitud_numero' => 6,
            'ejercicio' => 2026,
            'siguiente_numero' => 9130,
            'activa' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $serie = SerieDocumento::activaPara('boleta');

    expect($serie->siguiente_numero)->toBe(9130)
        ->and($serie->formatearFolio(9130, 2026))->toBe('BOL009130');
});

it('deja consultar una tarifa que ya no se puede editar', function () {
    $tarifa = Boleta::factory()->create()->tarifa;

    Livewire::test(TarifaResource::getPages()['index']->getPage())
        ->assertTableActionDisabled('edit', $tarifa)
        ->assertTableActionEnabled('view', $tarifa)
        ->mountTableAction('view', $tarifa)
        ->assertHasNoTableActionErrors();
});

it('deshabilita el borrado de un catalogo en uso y lo deja en uno libre', function () {
    $usada = Boleta::factory()->create()->tarifa;
    $libre = Tarifa::factory()->create(['vigente_desde' => '2030-01-01']);

    Livewire::test(TarifaResource::getPages()['index']->getPage())
        ->assertTableActionDisabled('delete', $usada)
        ->assertTableActionEnabled('delete', $libre);
});
