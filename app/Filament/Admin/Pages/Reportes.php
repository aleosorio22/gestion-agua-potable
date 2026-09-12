<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Models\Boleta;
use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Predio;
use App\Models\Sector;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Los papeles que la oficina necesita sacar del sistema.
 *
 * Cada reporte responde una pregunta concreta que alguien hace en la práctica:
 * cuánto entró hoy en caja, quién debe, qué casas faltan por leer, dónde se
 * está yendo el agua. No son volcados de tablas.
 *
 * Agregar uno nuevo es una entrada en `getReportes()` más su método
 * `consultaX()`; la generación y la vista no se tocan.
 */
class Reportes extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Reportes;

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'reportes';

    protected string $view = 'filament.admin.pages.reportes';

    /**
     * Filtros que comparten los reportes que los admiten.
     *
     * @var array<string, mixed>
     */
    public ?array $filtros = [];

    public function getTitle(): string
    {
        return 'Reportes';
    }

    public function mount(): void
    {
        $this->filtrosForm->fill([
            'periodo_id' => Periodo::vigente()?->id,
            'desde' => now()->startOfMonth()->toDateString(),
            'hasta' => now()->toDateString(),
        ]);
    }

    /**
     * Los reportes con su ficha: qué contesta cada uno y qué filtros usa.
     *
     * `filtros` no es decorativo — la vista muestra solo los que aplican, para
     * que nadie elija un rango de fechas en un reporte que lo ignora.
     *
     * @return array<string, array{titulo: string, descripcion: string, icono: string, grupo: string, filtros: array<int, string>}>
     */
    public function getReportes(): array
    {
        return [
            'corteDeCaja' => [
                'titulo' => 'Corte de caja',
                'descripcion' => 'Cuánto se cobró y con qué, en el rango de fechas',
                'icono' => 'heroicon-o-banknotes',
                'grupo' => 'Cobranza',
                'filtros' => ['fechas'],
            ],
            'mora' => [
                'titulo' => 'Cuentas por cobrar',
                'descripcion' => 'Boletas vencidas, ordenadas por antigüedad',
                'icono' => 'heroicon-o-exclamation-triangle',
                'grupo' => 'Cobranza',
                'filtros' => [],
            ],
            'estadoDeCuenta' => [
                'titulo' => 'Estado de cuenta',
                'descripcion' => 'Lo que debe cada vecino, sumando sus servicios',
                'icono' => 'heroicon-o-scale',
                'grupo' => 'Cobranza',
                'filtros' => [],
            ],
            'boletas' => [
                'titulo' => 'Boletas emitidas',
                'descripcion' => 'Facturación del período',
                'icono' => 'heroicon-o-receipt-percent',
                'grupo' => 'Cobranza',
                'filtros' => ['periodo'],
            ],

            'rutaDeLectura' => [
                'titulo' => 'Hoja de ruta',
                'descripcion' => 'Para salir a leer en papel, con espacio para anotar',
                'icono' => 'heroicon-o-map-pin',
                'grupo' => 'Operación',
                'filtros' => ['periodo', 'sector'],
            ],
            'lecturas' => [
                'titulo' => 'Lecturas tomadas',
                'descripcion' => 'Consumo registrado en el período, por sector',
                'icono' => 'heroicon-o-clipboard-document-list',
                'grupo' => 'Operación',
                'filtros' => ['periodo'],
            ],
            'pendientesDeLectura' => [
                'titulo' => 'Faltan por leer',
                'descripcion' => 'Contadores sin lectura en el período',
                'icono' => 'heroicon-o-question-mark-circle',
                'grupo' => 'Operación',
                'filtros' => ['periodo', 'sector'],
            ],
            'consumoPorSector' => [
                'titulo' => 'Consumo por sector',
                'descripcion' => 'Cuánta agua se distribuyó en cada zona',
                'icono' => 'heroicon-o-chart-bar',
                'grupo' => 'Operación',
                'filtros' => ['periodo'],
            ],

            'clientes' => [
                'titulo' => 'Clientes',
                'descripcion' => 'Padrón de titulares del servicio',
                'icono' => 'heroicon-o-users',
                'grupo' => 'Padrón',
                'filtros' => [],
            ],
            'contadores' => [
                'titulo' => 'Contadores',
                'descripcion' => 'Medidores instalados y su estado',
                'icono' => 'heroicon-o-cpu-chip',
                'grupo' => 'Padrón',
                'filtros' => ['sector'],
            ],
            'predios' => [
                'titulo' => 'Predios',
                'descripcion' => 'Propiedades por sector y su respaldo documental',
                'icono' => 'heroicon-o-map',
                'grupo' => 'Padrón',
                'filtros' => [],
            ],

            'anulaciones' => [
                'titulo' => 'Anulaciones y reversos',
                'descripcion' => 'Boletas anuladas y pagos revertidos, con su motivo',
                'icono' => 'heroicon-o-shield-exclamation',
                'grupo' => 'Control',
                'filtros' => ['fechas'],
            ],
            'resumenDelPeriodo' => [
                'titulo' => 'Resumen del período',
                'descripcion' => 'El consolidado del mes para la junta',
                'icono' => 'heroicon-o-document-chart-bar',
                'grupo' => 'Control',
                'filtros' => ['periodo'],
            ],
        ];
    }

    /**
     * Los reportes ordenados por grupo, conservando su clave.
     *
     * `groupBy()` reindexa por defecto y deja los reportes numerados 0, 1, 2:
     * la vista terminaba mandando `generarPdf('0')` y el servidor contestaba
     * 404 porque esa clave no existe. `preserveKeys` es lo que lo evita.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function getReportesPorGrupo(): array
    {
        return collect($this->getReportes())
            ->groupBy('grupo', preserveKeys: true)
            ->all();
    }

    public function filtrosForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('filtros')
            ->components([
                Section::make('Filtros')
                    ->description('Se aplican a los reportes que los admiten; cada tarjeta indica cuáles usa.')
                    ->columns(3)
                    ->schema([
                        Select::make('periodo_id')
                            ->label('Período')
                            ->native(false)
                            ->options(fn (): array => Periodo::query()
                                ->orderByDesc('anio')
                                ->orderByDesc('mes')
                                ->get()
                                ->pluck('etiqueta', 'id')
                                ->all())
                            ->placeholder('Período vigente'),

                        DatePicker::make('desde')
                            ->label('Desde')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('hasta')
                            ->label('Hasta')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('desde')
                            ->validationMessages([
                                'after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
                            ]),

                        Select::make('sector_id')
                            ->label('Sector')
                            ->native(false)
                            ->options(fn (): array => Sector::query()
                                ->orderBy('orden')
                                ->pluck('nombre', 'id')
                                ->all())
                            ->placeholder('Todos los sectores'),
                    ]),
            ]);
    }

    public function generarPdf(string $reporte): StreamedResponse
    {
        $datos = $this->datosDe($reporte);

        $pdf = Pdf::loadView("reportes.{$reporte}", $datos);

        if ($datos['apaisado'] ?? false) {
            $pdf->setPaper('letter', 'landscape');
        }

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $this->nombreDelArchivo($reporte, 'pdf'),
        );
    }

    public function generarExcel(string $reporte): BinaryFileResponse
    {
        $this->verificarQueExista($reporte);

        $clase = 'App\\Exports\\'.Str::studly($reporte).'Export';

        abort_unless(class_exists($clase), 404, "No existe la exportación [{$clase}].");

        return Excel::download(
            new $clase($this->filtrosAplicados()),
            $this->nombreDelArchivo($reporte, 'xlsx'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function datosDe(string $reporte): array
    {
        $this->verificarQueExista($reporte);

        $metodo = 'consulta'.Str::studly($reporte);

        abort_unless(method_exists($this, $metodo), 404, "No existe la consulta para [{$reporte}].");

        return array_merge($this->{$metodo}(), ['fecha' => now()]);
    }

    private function verificarQueExista(string $reporte): void
    {
        abort_unless(array_key_exists($reporte, $this->getReportes()), 404);
    }

    private function nombreDelArchivo(string $reporte, string $extension): string
    {
        return 'reporte-'.Str::kebab($reporte).'-'.now()->format('Y-m-d').'.'.$extension;
    }

    /**
     * Los filtros ya resueltos, con sus valores por defecto.
     *
     * @return array{periodo: ?Periodo, desde: string, hasta: string, sector: ?Sector}
     */
    private function filtrosAplicados(): array
    {
        $filtros = $this->filtros ?? [];

        return [
            'periodo' => filled($filtros['periodo_id'] ?? null)
                ? Periodo::find($filtros['periodo_id'])
                : Periodo::vigente(),
            'desde' => $filtros['desde'] ?? now()->startOfMonth()->toDateString(),
            'hasta' => $filtros['hasta'] ?? now()->toDateString(),
            'sector' => filled($filtros['sector_id'] ?? null)
                ? Sector::find($filtros['sector_id'])
                : null,
        ];
    }

    // ── Cobranza ─────────────────────────────────────────────────────

    /**
     * Con qué se cobró y cuánto, en el rango elegido.
     *
     * Es el reporte con el que cierra la ventanilla: el efectivo tiene que
     * coincidir con la gaveta, y los depósitos con el estado de cuenta del
     * banco. Por eso va desglosado por método y no solo el total.
     *
     * @return array<string, mixed>
     */
    private function consultaCorteDeCaja(): array
    {
        ['desde' => $desde, 'hasta' => $hasta] = $this->filtrosAplicados();

        $pagos = Pago::query()
            ->vigentes()
            // Con la hora explícita: el cast `date` guarda «2026-09-12
            // 00:00:00», así que comparar contra «2026-09-12» a secas deja
            // fuera todo lo cobrado el último día del rango.
            ->whereBetween('fecha_pago', [$desde.' 00:00:00', $hasta.' 23:59:59'])
            ->with(['boleta.cliente', 'metodoPago', 'usuario'])
            ->orderBy('fecha_pago')
            ->orderBy('numero')
            ->get();

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'pagos' => $pagos,
            'porMetodo' => $pagos->groupBy(fn (Pago $pago): string => $pago->metodoPago?->nombre ?? 'Sin método'),
            'total' => $pagos->sum(fn (Pago $pago): float => (float) $pago->monto),
            'subtitulo' => 'Del '.$this->formatear($desde).' al '.$this->formatear($hasta),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function consultaMora(): array
    {
        $boletas = Boleta::query()
            ->vencidas()
            ->with(['cliente', 'periodo', 'lectura.contador'])
            ->orderBy('fecha_vencimiento')
            ->get();

        return [
            'boletas' => $boletas,
            'total' => $boletas->sum(fn (Boleta $boleta): float => $boleta->saldo),
            'subtitulo' => $boletas->isEmpty()
                ? 'No hay boletas vencidas'
                : $boletas->count().' boletas vencidas',
        ];
    }

    /**
     * Lo que debe cada vecino, sumando todos sus servicios.
     *
     * Usa el mismo agregado que el tablero: el saldo sale de los pagos y no es
     * columna, así que calcularlo en PHP costaría una consulta por cliente.
     *
     * @return array<string, mixed>
     */
    private function consultaEstadoDeCuenta(): array
    {
        $clientes = Cliente::query()
            ->conEstadoDeCuenta()
            ->withCount('contadores')
            ->orderByDesc('deuda')
            ->get()
            ->filter(fn (Cliente $cliente): bool => $cliente->deuda_total > 0);

        return [
            'clientes' => $clientes,
            'total' => $clientes->sum(fn (Cliente $cliente): float => $cliente->deuda_total),
            'subtitulo' => $clientes->count().' vecinos con saldo pendiente',
            'apaisado' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function consultaBoletas(): array
    {
        $periodo = $this->filtrosAplicados()['periodo'];

        return [
            'periodo' => $periodo,
            'boletas' => $periodo
                ? Boleta::query()
                    ->where('periodo_id', $periodo->id)
                    ->vigentes()
                    ->with(['cliente', 'periodo'])
                    ->orderBy('numero')
                    ->get()
                : collect(),
            'subtitulo' => $periodo ? 'Período '.$periodo->etiqueta_larga : 'Sin período seleccionado',
        ];
    }

    // ── Operación ────────────────────────────────────────────────────

    /**
     * La hoja que el lector se lleva a la calle.
     *
     * Tiene una casilla en blanco para anotar a mano: es el respaldo para
     * cuando no hay señal o el celular se queda sin batería a mitad del
     * recorrido, y lo que permite trabajar a quien no tiene teléfono.
     *
     * @return array<string, mixed>
     */
    private function consultaRutaDeLectura(): array
    {
        ['periodo' => $periodo, 'sector' => $sector] = $this->filtrosAplicados();

        $contadores = $periodo
            ? Contador::query()
                ->activos()
                ->sinLecturaEn($periodo->id)
                ->when($sector, fn ($consulta) => $consulta->whereHas(
                    'predio',
                    fn ($predio) => $predio->where('sector_id', $sector->id),
                ))
                ->with(['cliente', 'predio.sector', 'paja'])
                ->get()
                ->sortBy([
                    fn (Contador $c): int => $c->predio?->sector?->orden ?? 999,
                    fn (Contador $c): string => (string) $c->predio?->numero_casa,
                ])
                ->groupBy(fn (Contador $c): string => $c->predio?->sector?->nombre ?? 'Sin sector')
            : collect();

        return [
            'periodo' => $periodo,
            'contadoresPorSector' => $contadores,
            'subtitulo' => trim(($periodo ? 'Período '.$periodo->etiqueta : 'Sin período')
                .($sector ? ' — Sector '.$sector->nombre : '')),
            'apaisado' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function consultaLecturas(): array
    {
        $periodo = $this->filtrosAplicados()['periodo'];

        $lecturas = $periodo
            ? Lectura::query()
                ->where('periodo_id', $periodo->id)
                ->with(['contador.predio.sector', 'contador.cliente', 'usuario'])
                ->get()
                ->sortBy(fn (Lectura $l): int => $l->contador?->predio?->sector?->orden ?? 999)
                ->groupBy(fn (Lectura $l): string => $l->contador?->predio?->sector?->nombre ?? 'Sin sector')
            : collect();

        return [
            'periodo' => $periodo,
            'lecturasPorSector' => $lecturas,
            'subtitulo' => $periodo ? 'Período '.$periodo->etiqueta_larga : 'Sin período seleccionado',
        ];
    }

    /**
     * Qué casas quedaron sin visitar.
     *
     * Es el reporte con el que la oficina cierra el ciclo: mientras haya
     * contadores acá, hay agua entregada que nadie va a cobrar.
     *
     * @return array<string, mixed>
     */
    private function consultaPendientesDeLectura(): array
    {
        ['periodo' => $periodo, 'sector' => $sector] = $this->filtrosAplicados();

        $contadores = $periodo
            ? Contador::query()
                ->activos()
                ->sinLecturaEn($periodo->id)
                ->when($sector, fn ($consulta) => $consulta->whereHas(
                    'predio',
                    fn ($predio) => $predio->where('sector_id', $sector->id),
                ))
                ->with(['cliente', 'predio.sector'])
                ->get()
                ->sortBy(fn (Contador $c): int => $c->predio?->sector?->orden ?? 999)
            : collect();

        $total = $periodo ? Contador::query()->activos()->count() : 0;

        return [
            'periodo' => $periodo,
            'contadores' => $contadores,
            'leidos' => $total - $contadores->count(),
            'total' => $total,
            'subtitulo' => $periodo
                ? 'Período '.$periodo->etiqueta.' — faltan '.$contadores->count().' de '.$total
                : 'Sin período seleccionado',
        ];
    }

    /**
     * Cuánta agua se distribuyó en cada zona.
     *
     * Un sector cuyo consumo se dispara sin que crezca el padrón suele ser una
     * fuga, no vecinos gastando más. Este reporte es lo que permite verlo.
     *
     * @return array<string, mixed>
     */
    private function consultaConsumoPorSector(): array
    {
        $periodo = $this->filtrosAplicados()['periodo'];

        $porSector = $periodo
            ? Lectura::query()
                ->where('periodo_id', $periodo->id)
                ->with('contador.predio.sector')
                ->get()
                ->groupBy(fn (Lectura $l): string => $l->contador?->predio?->sector?->nombre ?? 'Sin sector')
                ->map(fn ($lecturas, string $sector): array => [
                    'sector' => $sector,
                    'servicios' => $lecturas->count(),
                    'consumo' => $lecturas->sum(fn (Lectura $l): float => (float) $l->consumo_m3),
                    'promedio' => round($lecturas->avg(fn (Lectura $l): float => (float) $l->consumo_m3), 2),
                    'mayor' => $lecturas->max(fn (Lectura $l): float => (float) $l->consumo_m3),
                ])
                ->sortByDesc('consumo')
                ->values()
            : collect();

        return [
            'periodo' => $periodo,
            'sectores' => $porSector,
            'totalConsumo' => $porSector->sum('consumo'),
            'totalServicios' => $porSector->sum('servicios'),
            'subtitulo' => $periodo ? 'Período '.$periodo->etiqueta_larga : 'Sin período seleccionado',
        ];
    }

    // ── Padrón ───────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function consultaClientes(): array
    {
        $clientes = Cliente::query()
            ->withCount('contadores')
            ->orderBy('nombre')
            ->get();

        return [
            'clientes' => $clientes,
            'subtitulo' => $clientes->count().' clientes registrados',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function consultaContadores(): array
    {
        $sector = $this->filtrosAplicados()['sector'];

        $contadores = Contador::query()
            ->when($sector, fn ($consulta) => $consulta->whereHas(
                'predio',
                fn ($predio) => $predio->where('sector_id', $sector->id),
            ))
            ->with(['cliente', 'predio.sector', 'paja'])
            ->orderBy('codigo')
            ->get();

        return [
            'contadores' => $contadores,
            'subtitulo' => $contadores->count().' contadores'
                .($sector ? ' en el sector '.$sector->nombre : ''),
            'apaisado' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function consultaPredios(): array
    {
        // `withCount` en la misma consulta: contar por fila dentro de un map
        // hace una consulta por predio, y con el padrón lleno se siente.
        $predios = Predio::query()
            ->with('sector')
            ->withCount(['contadores', 'documentos'])
            ->get()
            ->sortBy(fn (Predio $p): int => $p->sector?->orden ?? 999)
            ->groupBy(fn (Predio $p): string => $p->sector?->nombre ?? 'Sin sector');

        return [
            'prediosPorSector' => $predios,
            'subtitulo' => $predios->flatten()->count().' predios registrados',
        ];
    }

    // ── Control ──────────────────────────────────────────────────────

    /**
     * Lo que se dejó sin efecto, con su motivo y quién lo hizo.
     *
     * Ni las boletas ni los pagos se borran: se anulan y se revierten. Este
     * reporte es el que vuelve auditable esa decisión.
     *
     * @return array<string, mixed>
     */
    private function consultaAnulaciones(): array
    {
        ['desde' => $desde, 'hasta' => $hasta] = $this->filtrosAplicados();

        $boletas = Boleta::query()
            ->anuladas()
            ->whereBetween('anulada_en', [$desde.' 00:00:00', $hasta.' 23:59:59'])
            ->with(['cliente', 'periodo', 'anuladaPor'])
            ->orderByDesc('anulada_en')
            ->get();

        $pagos = Pago::query()
            ->revertidos()
            ->whereBetween('revertido_en', [$desde.' 00:00:00', $hasta.' 23:59:59'])
            ->with(['boleta.cliente', 'revertidoPor'])
            ->orderByDesc('revertido_en')
            ->get();

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'boletas' => $boletas,
            'pagos' => $pagos,
            'subtitulo' => 'Del '.$this->formatear($desde).' al '.$this->formatear($hasta),
        ];
    }

    /**
     * El consolidado que se lleva a la junta.
     *
     * Una sola hoja que responde: cuánta agua se entregó, cuánto se facturó,
     * cuánto se cobró y cuánto quedó debiendo.
     *
     * @return array<string, mixed>
     */
    private function consultaResumenDelPeriodo(): array
    {
        $periodo = $this->filtrosAplicados()['periodo'];

        if ($periodo === null) {
            return ['periodo' => null, 'resumen' => [], 'subtitulo' => 'Sin período seleccionado'];
        }

        $boletas = Boleta::query()->where('periodo_id', $periodo->id)->vigentes()->get();
        $lecturas = Lectura::query()->where('periodo_id', $periodo->id)->get();

        $cobrado = Pago::query()
            ->vigentes()
            ->whereIn('boleta_id', $boletas->pluck('id'))
            ->sum('monto');

        return [
            'periodo' => $periodo,
            'resumen' => [
                'Contadores activos' => Contador::query()->activos()->count(),
                'Lecturas tomadas' => $lecturas->count(),
                'Consumo total' => number_format($lecturas->sum(fn (Lectura $l): float => (float) $l->consumo_m3), 2).' m³',
                'Consumo promedio' => number_format((float) $lecturas->avg(fn (Lectura $l): float => (float) $l->consumo_m3), 2).' m³',
                'Boletas emitidas' => $boletas->count(),
                'Facturado' => 'Q'.number_format($boletas->sum(fn (Boleta $b): float => (float) $b->monto), 2),
                'Cobrado' => 'Q'.number_format((float) $cobrado, 2),
                'Por cobrar' => 'Q'.number_format($boletas->sum(fn (Boleta $b): float => $b->saldo), 2),
                'Boletas anuladas' => Boleta::query()->where('periodo_id', $periodo->id)->anuladas()->count(),
            ],
            'subtitulo' => 'Período '.$periodo->etiqueta_larga,
        ];
    }

    private function formatear(string $fecha): string
    {
        return Carbon::parse($fecha)->format('d/m/Y');
    }
}
