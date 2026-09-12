<?php

use App\Filament\Admin\Pages\Reportes;
use App\Models\Boleta;
use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Predio;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Los reportes de la oficina.
 *
 * Lo que más importa demostrar es que generen también con la base vacía: una
 * oficina recién instalada es justo cuando alguien toca el botón por curiosidad,
 * y un reporte que revienta ahí es lo primero que se ve del sistema.
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

/** Una oficina con algo de todo: lecturas, boletas, pagos y mora. */
function oficinaConMovimiento(): Periodo
{
    $periodo = Periodo::factory()->create();
    $sector = Sector::factory()->create(['nombre' => 'El Porvenir', 'orden' => 1]);
    $predio = Predio::factory()->create(['sector_id' => $sector->id]);
    $contador = Contador::factory()->create(['predio_id' => $predio->id]);

    Lectura::factory()->create(['contador_id' => $contador->id, 'periodo_id' => $periodo->id]);

    $pagada = Boleta::factory()->create(['periodo_id' => $periodo->id, 'monto' => 100]);
    Pago::factory()->create(['boleta_id' => $pagada->id, 'monto' => 100, 'fecha_pago' => now()]);

    Boleta::factory()->create([
        'periodo_id' => $periodo->id,
        'monto' => 64,
        'fecha_vencimiento' => now()->subMonth(),
    ]);

    Contador::factory()->count(2)->create();

    return $periodo;
}

it('pinta la pantalla de reportes', function () {
    Livewire::test(Reportes::class)->assertSuccessful();
});

it('agrupa los reportes por el trabajo al que sirven', function () {
    $grupos = array_keys(app(Reportes::class)->getReportesPorGrupo());

    expect($grupos)->toContain('Cobranza', 'Operación', 'Padrón', 'Control');
});

it('conserva la clave de cada reporte al agruparlos', function () {
    // `groupBy()` reindexa por defecto: la vista terminaba mandando
    // `generarPdf('0')` y el servidor contestaba 404, con la pantalla cargando
    // perfecto. Es el tipo de error que solo se ve apretando el botón.
    $claves = collect(app(Reportes::class)->getReportesPorGrupo())
        ->flatMap(fn ($delGrupo): array => collect($delGrupo)->keys()->all())
        ->all();

    expect($claves)->toEqualCanonicalizing(array_keys(app(Reportes::class)->getReportes()));
});

it('declara para cada reporte que filtros usa', function () {
    // La vista lo muestra en la tarjeta: sin esto, alguien elige un rango de
    // fechas en un reporte que lo ignora y no entiende por qué no cambia nada.
    foreach (app(Reportes::class)->getReportes() as $clave => $reporte) {
        expect($reporte)->toHaveKeys(['titulo', 'descripcion', 'icono', 'grupo', 'filtros'], "falta ficha en {$clave}");
    }
});

it('tiene una consulta y una exportacion por cada reporte', function () {
    $pagina = new ReflectionClass(Reportes::class);

    foreach (array_keys(app(Reportes::class)->getReportes()) as $clave) {
        $metodo = 'consulta'.Str::studly($clave);
        $export = 'App\\Exports\\'.Str::studly($clave).'Export';

        expect($pagina->hasMethod($metodo))->toBeTrue("falta {$metodo}()")
            ->and(class_exists($export))->toBeTrue("falta {$export}");
    }
});

// Uno por prueba y no los trece de una: dompdf consume bastante memoria y
// juntarlos en una sola petición agota el límite de PHP, algo que en producción
// no pasa porque cada descarga es una petición aparte. De paso, cuando algo se
// rompe, el nombre de la prueba dice cuál reporte fue.
it('genera el reporte en pdf con datos dentro', function (string $clave) {
    $periodo = oficinaConMovimiento();

    Livewire::test(Reportes::class)
        ->set('filtros.periodo_id', $periodo->id)
        ->call('generarPdf', $clave);
})->with('reportes')->throwsNoExceptions();

it('genera el reporte con la base vacia', function (string $clave) {
    // Sin período, sin clientes, sin nada: el estado de una instalación nueva,
    // que es justo cuando alguien toca el botón por curiosidad.
    Livewire::test(Reportes::class)->call('generarPdf', $clave);
})->with('reportes')->throwsNoExceptions();

it('rechaza un reporte que no existe', function () {
    $pagina = new Reportes;

    (new ReflectionMethod($pagina, 'verificarQueExista'))->invoke($pagina, 'inventado');
})->throws(NotFoundHttpException::class);

dataset('reportes', fn (): array => array_keys(app(Reportes::class)->getReportes()));

it('respeta el rango de fechas en el corte de caja', function () {
    $boleta = Boleta::factory()->create(['monto' => 500]);

    Pago::factory()->create([
        'boleta_id' => $boleta->id,
        'monto' => 40,
        'fecha_pago' => now()->toDateString(),
    ]);
    Pago::factory()->create([
        'boleta_id' => $boleta->id,
        'monto' => 90,
        'fecha_pago' => now()->subMonths(3)->toDateString(),
    ]);

    $datos = reporte('corteDeCaja', ['desde' => now()->startOfMonth()->toDateString(), 'hasta' => now()->toDateString()]);

    // Solo el de este mes: el de hace tres meses ya se cuadró en su momento.
    expect($datos['total'])->toBe(40.0)
        ->and($datos['pagos'])->toHaveCount(1);
});

it('deja fuera del corte de caja los pagos revertidos', function () {
    $boleta = Boleta::factory()->create(['monto' => 500]);

    Pago::factory()->create(['boleta_id' => $boleta->id, 'monto' => 60, 'fecha_pago' => now()]);

    $revertido = Pago::factory()->create(['boleta_id' => $boleta->id, 'monto' => 30, 'fecha_pago' => now()]);
    $revertido->revertir(auth()->user(), 'Cheque rechazado');

    // Un reverso no entra en la caja: el efectivo no está en la gaveta.
    expect(reporte('corteDeCaja')['total'])->toBe(60.0);
});

it('desglosa el corte de caja por metodo de pago', function () {
    $boleta = Boleta::factory()->create(['monto' => 500]);
    Pago::factory()->count(2)->create(['boleta_id' => $boleta->id, 'monto' => 25, 'fecha_pago' => now()]);

    expect(reporte('corteDeCaja')['porMetodo'])->not->toBeEmpty();
});

it('suma en el estado de cuenta lo que debe cada vecino', function () {
    $cliente = Cliente::factory()->create();

    Boleta::factory()->create(['cliente_id' => $cliente->id, 'monto' => 64]);
    Boleta::factory()->create(['cliente_id' => $cliente->id, 'monto' => 40]);

    $saldada = Boleta::factory()->create(['cliente_id' => $cliente->id, 'monto' => 50]);
    Pago::factory()->create(['boleta_id' => $saldada->id, 'monto' => 50]);

    $datos = reporte('estadoDeCuenta');
    $fila = $datos['clientes']->firstWhere('id', $cliente->id);

    expect($fila->deuda_total)->toBe(104.0);
});

it('deja fuera del estado de cuenta a quien no debe nada', function () {
    $alDia = Cliente::factory()->create();
    $boleta = Boleta::factory()->create(['cliente_id' => $alDia->id, 'monto' => 50]);
    Pago::factory()->create(['boleta_id' => $boleta->id, 'monto' => 50]);

    expect(reporte('estadoDeCuenta')['clientes']->firstWhere('id', $alDia->id))->toBeNull();
});

it('lleva a la hoja de ruta solo lo que falta por leer', function () {
    $periodo = Periodo::factory()->create();
    $leido = Lectura::factory()->create(['periodo_id' => $periodo->id])->contador;
    $pendiente = Contador::factory()->create();

    $datos = reporte('rutaDeLectura', ['periodo_id' => $periodo->id]);
    $codigos = $datos['contadoresPorSector']->flatten()->pluck('codigo');

    expect($codigos)->toContain($pendiente->codigo)
        ->and($codigos)->not->toContain($leido->codigo);
});

it('acota la hoja de ruta al sector elegido', function () {
    $periodo = Periodo::factory()->create();
    $norte = Sector::factory()->create(['orden' => 1]);

    $delNorte = Contador::factory()->create([
        'predio_id' => Predio::factory()->create(['sector_id' => $norte->id])->id,
    ]);
    $ajeno = Contador::factory()->create();

    $datos = reporte('rutaDeLectura', ['periodo_id' => $periodo->id, 'sector_id' => $norte->id]);
    $codigos = $datos['contadoresPorSector']->flatten()->pluck('codigo');

    expect($codigos)->toContain($delNorte->codigo)
        ->and($codigos)->not->toContain($ajeno->codigo);
});

it('cuenta el avance en el reporte de pendientes de lectura', function () {
    $periodo = Periodo::factory()->create();
    Lectura::factory()->create(['periodo_id' => $periodo->id]);
    Contador::factory()->count(2)->create();

    $datos = reporte('pendientesDeLectura', ['periodo_id' => $periodo->id]);

    expect($datos['total'])->toBe(3)
        ->and($datos['leidos'])->toBe(1)
        ->and($datos['contadores'])->toHaveCount(2);
});

it('agrupa el consumo por sector y lo ordena de mayor a menor', function () {
    $periodo = Periodo::factory()->create();

    $mucho = Sector::factory()->create(['nombre' => 'Alto consumo', 'orden' => 1]);
    $poco = Sector::factory()->create(['nombre' => 'Bajo consumo', 'orden' => 2]);

    foreach ([[$mucho, 90], [$poco, 10]] as [$sector, $consumo]) {
        $contador = Contador::factory()->create([
            'predio_id' => Predio::factory()->create(['sector_id' => $sector->id])->id,
        ]);

        Lectura::factory()->create([
            'contador_id' => $contador->id,
            'periodo_id' => $periodo->id,
            'lectura_anterior' => 0,
            'lectura_actual' => $consumo,
        ]);
    }

    $datos = reporte('consumoPorSector', ['periodo_id' => $periodo->id]);

    // El primero es donde más agua se fue: es lo que se mira para buscar fugas.
    expect($datos['sectores']->first()['sector'])->toBe('Alto consumo')
        ->and($datos['totalConsumo'])->toBe(100.0);
});

it('reúne en control las boletas anuladas y los pagos revertidos', function () {
    $boleta = Boleta::factory()->create(['monto' => 100]);
    $pago = Pago::factory()->create(['boleta_id' => $boleta->id, 'monto' => 30]);
    $pago->revertir(auth()->user(), 'Cheque rechazado');

    $anulada = Boleta::factory()->create();
    $anulada->anular(auth()->user(), 'Lectura mal tomada');

    $datos = reporte('anulaciones');

    expect($datos['boletas'])->toHaveCount(1)
        ->and($datos['pagos'])->toHaveCount(1)
        ->and($datos['boletas']->first()->motivo_anulacion)->toBe('Lectura mal tomada');
});

it('consolida el periodo para la junta', function () {
    $periodo = oficinaConMovimiento();

    $resumen = reporte('resumenDelPeriodo', ['periodo_id' => $periodo->id])['resumen'];

    expect($resumen)->toHaveKeys([
        'Contadores activos', 'Lecturas tomadas', 'Consumo total',
        'Boletas emitidas', 'Facturado', 'Cobrado', 'Por cobrar',
    ])
        ->and($resumen['Cobrado'])->toBe('Q100.00')
        ->and($resumen['Por cobrar'])->toBe('Q64.00');
});

it('cuenta los documentos de cada predio sin una consulta por fila', function () {
    Contador::factory()->count(15)->create();

    DB::enableQueryLog();
    $datos = reporte('predios');
    $consultas = count(DB::getQueryLog());
    DB::disableQueryLog();

    // `withCount` en la consulta, no un count() dentro del map: con el padrón
    // lleno, una consulta por predio se siente.
    expect($datos['prediosPorSector']->flatten())->toHaveCount(15)
        ->and($consultas)->toBeLessThan(5);
});

it('genera la exportacion de excel', function (string $clave) {
    oficinaConMovimiento();

    Livewire::test(Reportes::class)->call('generarExcel', $clave);
})->with('reportes')->throwsNoExceptions();

it('exige permiso para entrar a reportes', function () {
    expect(Permission::where('name', 'like', '%Reportes%')->exists())
        ->toBeTrue('la página debe tener permiso de Shield, o la ve cualquiera');
});

/**
 * Ejecuta la consulta de un reporte con los filtros dados.
 *
 * @param  array<string, mixed>  $filtros
 * @return array<string, mixed>
 */
function reporte(string $clave, array $filtros = []): array
{
    $pagina = new Reportes;
    $pagina->filtros = $filtros;

    $metodo = new ReflectionMethod($pagina, 'consulta'.Str::studly($clave));

    return $metodo->invoke($pagina);
}
