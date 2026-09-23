<?php

namespace Database\Seeders;

use App\Models\Boleta;
use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Paja;
use App\Models\Periodo;
use App\Models\Predio;
use App\Models\Sector;
use App\Models\Tarifa;
use App\Models\User;
use App\Services\EmisorBoletas;
use App\Services\RegistradorPagos;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Padrón de prueba con el año de facturación ya corrido.
 *
 * Siembra vecinos en los estados de cuenta que la oficina ve todos los días
 * —al día, debiendo tres meses, con un abono a medias, con un cheque que el
 * banco rechazó— para poder probar cobranza, portal y reportes sin esperar a
 * que pasen los meses.
 *
 * No inserta boletas ni pagos a mano: llama a EmisorBoletas y RegistradorPagos
 * con el reloj puesto en la fecha de cada mes (ver enLaFecha()). Así los
 * correlativos, los vencimientos y los snapshots de tarifa salen exactamente
 * como saldrían en producción, y un cambio en esas reglas se refleja aquí sin
 * tener que mantener una copia del cálculo.
 *
 * Siembra los meses del año que ya corrieron, así que en enero apenas habrá
 * uno: los escenarios de mora necesitan que el año haya avanzado.
 *
 * Se puede volver a correr: todo lo que siembra queda marcado con el prefijo
 * de código DEMO- y se borra al empezar. Los catálogos que comparte con los
 * datos reales —períodos, tarifas, sectores— se crean si faltan y no se tocan.
 *
 *   php artisan db:seed --class=DatosDePruebaSeeder
 */
class DatosDePruebaSeeder extends Seeder
{
    /** Marca lo sembrado por este seeder; es lo que permite volver a correrlo. */
    private const PREFIJO_CLIENTE = 'DEMO-';

    private const PREFIJO_CONTADOR = 'CTRD-';

    /** Cuántos vecinos de comportamiento mixto se agregan además de los casos con nombre. */
    private const CLIENTES_DE_RELLENO = 12;

    private int $anio;

    private User $lector;

    private User $cajera;

    private EmisorBoletas $emisor;

    private RegistradorPagos $registrador;

    /** @var Collection<int, Periodo> */
    private Collection $periodos;

    /** @var Collection<int, Sector> */
    private Collection $sectores;

    /** @var Collection<int, Paja> */
    private Collection $pajas;

    /** @var \Illuminate\Support\Collection<string, MetodoPago> */
    private $metodos;

    private int $contadoresCreados = 0;

    public function run(): void
    {
        $this->anio = (int) now()->year;
        $this->emisor = new EmisorBoletas;
        $this->registrador = new RegistradorPagos;

        $this->cajera = $this->usuarioDeVentanilla();
        $this->lector = $this->usuarioLector();

        $this->limpiarSiembraAnterior();

        $this->periodos = $this->abrirPeriodosDelAnio();
        $this->sectores = $this->asegurarSectores();
        $this->pajas = $this->asegurarTarifas();
        $this->metodos = MetodoPago::query()->where('activo', true)->get()->keyBy('codigo');

        if ($this->metodos->isEmpty()) {
            throw new RuntimeException('No hay métodos de pago: corré primero «php artisan db:seed».');
        }

        $casos = [];
        $numero = 0;

        foreach ($this->escenarios() as $escenario) {
            $cliente = $this->crearCliente(++$numero, $escenario);
            $casos[$cliente->codigo] = $escenario['caso'];

            $this->command?->getOutput()->write('.');
        }

        $this->command?->getOutput()->writeln('');

        $this->cerrarMesesLiquidados();
        $this->mostrarResumen($casos);
    }

    /**
     * Los casos que interesa poder abrir en pantalla y reconocer de inmediato.
     *
     * `sin_pagar` se cuenta desde la boleta más reciente hacia atrás: es la
     * forma natural de describir una mora («debe los últimos tres meses»).
     *
     * @return list<array{caso: string, sin_pagar?: int, desde?: int, parcial?: bool, revertir?: bool, anular?: bool, consumo?: string, estado?: string, sin_lecturas?: bool, servicios?: int, metodos?: list<string>}>
     */
    private function escenarios(): array
    {
        $casos = [
            ['caso' => 'Al día: todo pagado', 'sin_pagar' => 0],
            ['caso' => 'Debe solo el mes en curso (aún no vence)', 'sin_pagar' => 1],
            ['caso' => 'Debe 2 meses (uno ya vencido)', 'sin_pagar' => 2],
            ['caso' => 'Debe 3 meses (dos ya vencidos)', 'sin_pagar' => 3],
            ['caso' => 'No ha pagado nada en el año', 'sin_pagar' => 12],
            ['caso' => 'Abono parcial: debe la mitad del mes pasado', 'sin_pagar' => 2, 'parcial' => true],
            ['caso' => 'Cheque rechazado: el pago se revirtió y volvió a deber', 'sin_pagar' => 0, 'revertir' => true, 'metodos' => ['cheque']],
            ['caso' => 'Boleta anulada por lectura mal tomada', 'sin_pagar' => 1, 'anular' => true],
            ['caso' => 'Consumo con excedente alto', 'sin_pagar' => 1, 'consumo' => 'alto'],
            ['caso' => 'Servicio suspendido, 5 meses de mora', 'sin_pagar' => 5, 'estado' => 'inactivo'],
            ['caso' => 'Alta a mitad de año (julio)', 'desde' => 7, 'sin_pagar' => 1],
            ['caso' => 'Recién instalado, sin lecturas todavía', 'sin_lecturas' => true],
            ['caso' => 'Dos servicios en el mismo nombre', 'sin_pagar' => 1, 'servicios' => 2],
            ['caso' => 'Paga por transferencia y depósito', 'sin_pagar' => 0, 'metodos' => ['transferencia', 'deposito']],
        ];

        // Relleno para que las listas, los filtros y los totales tengan volumen.
        for ($i = 0; $i < self::CLIENTES_DE_RELLENO; $i++) {
            $casos[] = [
                'caso' => 'Comportamiento mixto',
                'sin_pagar' => fake()->numberBetween(0, 4),
                'parcial' => fake()->boolean(25),
                'consumo' => fake()->boolean(20) ? 'alto' : 'normal',
            ];
        }

        return $casos;
    }

    /**
     * @param  array<string, mixed>  $escenario
     */
    private function crearCliente(int $numero, array $escenario): Cliente
    {
        $cliente = Cliente::create([
            'codigo' => self::PREFIJO_CLIENTE.str_pad((string) $numero, 4, '0', STR_PAD_LEFT),
            'nombre' => $this->nombreDeVecino($numero),
            // Documentos derivados del número y no aleatorios: los DPI y NIT
            // son únicos en la tabla, y un aleatorio puede chocar con un
            // cliente real ya cargado.
            'dpi' => sprintf('29%011d', $numero),
            'nit' => sprintf('%06d-9', 900000 + $numero),
            'telefono' => sprintf('5%03d-%04d', 100 + $numero, 1000 + $numero),
            'email' => sprintf('vecino%02d@ejemplo.test', $numero),
            'direccion_notificacion' => null,
            'estado' => $escenario['estado'] ?? 'activo',
        ]);

        $mesDeAlta = $escenario['desde'] ?? 1;

        for ($servicio = 1; $servicio <= ($escenario['servicios'] ?? 1); $servicio++) {
            $contador = $this->instalarContador($cliente, $mesDeAlta);

            if ($escenario['sin_lecturas'] ?? false) {
                continue;
            }

            $this->facturarServicio($contador, $escenario);
        }

        return $cliente;
    }

    private function instalarContador(Cliente $cliente, int $mesDeAlta): Contador
    {
        $predio = Predio::create([
            'sector_id' => $this->sectores->random()->id,
            'aldea' => fake()->randomElement(['El Porvenir', 'San Antonio', 'Las Flores', 'Chuisuc', 'La Esperanza']),
            'zona' => (string) fake()->numberBetween(0, 4),
            'calle' => fake()->randomElement(['Principal', 'Del Calvario', 'A la escuela', null]),
            'numero_casa' => fake()->numerify('#-##'),
            'referencia' => fake()->optional()->randomElement([
                'frente a la tienda', 'a un costado del pozo', 'portón azul',
            ]),
        ]);

        $instalacion = $mesDeAlta > 1
            ? Carbon::create($this->anio, $mesDeAlta, 1)->subDays(fake()->numberBetween(1, 20))
            : Carbon::create($this->anio - 1, fake()->numberBetween(1, 12), fake()->numberBetween(1, 28));

        return Contador::create([
            'predio_id' => $predio->id,
            'cliente_id' => $cliente->id,
            'paja_id' => $this->pajas->random()->id,
            'codigo' => self::PREFIJO_CONTADOR.str_pad((string) ++$this->contadoresCreados, 4, '0', STR_PAD_LEFT),
            'fecha_instalacion' => $instalacion->toDateString(),
            'estado' => 'activo',
        ]);
    }

    /**
     * Corre el ciclo completo del año sobre un servicio: lectura del mes,
     * boleta a los dos días, y el cobro según el escenario.
     *
     * @param  array<string, mixed>  $escenario
     */
    private function facturarServicio(Contador $contador, array $escenario): void
    {
        $equivalencia = (float) $contador->paja->equivalencia_m3;
        // La primera lectura del medidor arranca en cero: LecturaObserver exige
        // que cada visita encadene con la anterior, y antes de la primera no hay.
        $acumulado = 0.0;
        $boletas = [];

        foreach ($this->periodos as $periodo) {
            if ($periodo->mes < ($escenario['desde'] ?? 1) || $periodo->esta_cerrado) {
                continue;
            }

            // Se lee al día 2 y se cobra al día 3: con 30 días de plazo, el mes
            // pasado ya está vencido y el corriente todavía no. Es lo que hace
            // que los estados de cuenta se vean distintos entre sí.
            $visita = $periodo->fecha_inicio->copy()->addDay();
            $emision = $periodo->fecha_inicio->copy()->addDays(2);

            if ($emision->isFuture()) {
                break;
            }

            $consumo = ($escenario['consumo'] ?? 'normal') === 'alto'
                ? round($equivalencia * fake()->randomFloat(2, 1.15, 1.8), 2)
                : round($equivalencia * fake()->randomFloat(2, 0.35, 0.95), 2);

            $lectura = $this->enLaFecha($visita, fn (): Lectura => Lectura::create([
                'contador_id' => $contador->id,
                'periodo_id' => $periodo->id,
                'usuario_id' => $this->lector->id,
                'lectura_anterior' => $acumulado,
                'lectura_actual' => $acumulado + $consumo,
                'fecha_lectura' => $visita->toDateString(),
            ]));

            $acumulado += $consumo;

            $boletas[] = $this->enLaFecha($emision, fn (): Boleta => $this->emisor->emitir($lectura));
        }

        $this->cobrar($boletas, $escenario);
    }

    /**
     * @param  list<Boleta>  $boletas  En orden, de la más vieja a la más reciente.
     * @param  array<string, mixed>  $escenario
     */
    private function cobrar(array $boletas, array $escenario): void
    {
        $total = count($boletas);
        $porPagar = max(0, $total - min($escenario['sin_pagar'] ?? 0, $total));

        for ($indice = 0; $indice < $porPagar; $indice++) {
            $this->pagar($boletas[$indice], (float) $boletas[$indice]->monto, $escenario);
        }

        // El abono va sobre la más vieja de las que quedaron debiendo, que es
        // como llega el vecino a ventanilla: paga algo de lo más atrasado.
        if (($escenario['parcial'] ?? false) && isset($boletas[$porPagar])) {
            $mitad = round((float) $boletas[$porPagar]->monto / 2, 2);
            $this->pagar($boletas[$porPagar], $mitad, $escenario);
        }

        if (($escenario['revertir'] ?? false) && $porPagar > 0) {
            // El reverso va sobre una boleta que ya venció: así deja al vecino
            // en mora, que es el caso que hay que poder ver en cobranza.
            $pagadas = array_slice($boletas, 0, $porPagar);
            $vencidas = array_values(array_filter(
                $pagadas,
                fn (Boleta $boleta): bool => $boleta->fecha_vencimiento->isPast()
            ));
            $candidatas = $vencidas !== [] ? $vencidas : $pagadas;

            $candidatas[array_key_last($candidatas)]
                ->pagos()
                ->latest('id')
                ->first()
                ?->revertir($this->cajera, 'El banco rechazó el cheque por fondos insuficientes.');
        }

        if (($escenario['anular'] ?? false) && $total > 0) {
            $boletas[$total - 1]->anular($this->cajera, 'Lectura mal tomada; se reemite con la corregida.');
        }
    }

    /**
     * @param  array<string, mixed>  $escenario
     */
    private function pagar(Boleta $boleta, float $monto, array $escenario): void
    {
        $metodo = $this->metodoDePago($escenario);
        $fecha = $this->fechaDeCobro($boleta);

        $this->enLaFecha($fecha, fn (): mixed => $this->registrador->registrar(
            boleta: $boleta,
            metodoPago: $metodo,
            usuario: $this->cajera,
            monto: $monto,
            referencia: $metodo->requiere_referencia ? fake()->numerify('##########') : null,
            fechaPago: $fecha->toDateString(),
        ));
    }

    /**
     * Unos pagan a los dos días y otros casi al vencimiento; nunca en el futuro.
     */
    private function fechaDeCobro(Boleta $boleta): Carbon
    {
        $fecha = $boleta->fecha_emision->copy()->addDays(fake()->numberBetween(2, 27));

        return $fecha->isFuture() ? Carbon::today() : $fecha;
    }

    /**
     * @param  array<string, mixed>  $escenario
     */
    private function metodoDePago(array $escenario): MetodoPago
    {
        // La mayoría paga en efectivo en ventanilla; el resto da variedad a los
        // cortes de caja.
        $codigos = $escenario['metodos'] ?? ['efectivo', 'efectivo', 'efectivo', 'cheque', 'transferencia', 'deposito'];

        $disponibles = array_values(array_filter(
            $codigos,
            fn (string $codigo): bool => $this->metodos->has($codigo)
        ));

        return $this->metodos[fake()->randomElement($disponibles ?: $this->metodos->keys()->all())];
    }

    /**
     * Ejecuta la acción como si hoy fuera la fecha dada.
     *
     * Es lo que permite reusar los servicios de emisión y cobro en vez de
     * armar las boletas a mano: ambos fechan con now(), así que con el reloj
     * corrido la boleta de marzo queda emitida y vencida en marzo, con su
     * created_at del día que corresponde.
     */
    private function enLaFecha(Carbon $fecha, callable $accion): mixed
    {
        // Restaura el reloj que había, no el real: si quien llama ya estaba
        // viajando en el tiempo —un test con travelTo()—, cancelárselo le
        // cambiaría el año a media siembra.
        $reloj = Carbon::getTestNow();

        Carbon::setTestNow($fecha->copy()->setTime(9, 30));

        try {
            return $accion();
        } finally {
            Carbon::setTestNow($reloj);
        }
    }

    /**
     * Cierra los meses ya liquidados y deja abiertos los dos últimos.
     *
     * Un año entero abierto no se parece a nada: en la oficina el mes que se
     * terminó de cobrar se cierra, y eso es justo lo que hay que poder probar
     * —que el lector no pueda meter lecturas ahí, que la boleta no se reemita—.
     * Los dos últimos quedan abiertos porque son los que se están trabajando.
     */
    private function cerrarMesesLiquidados(): void
    {
        $hastaMes = (int) now()->month - 2;

        $this->periodos
            ->filter(fn (Periodo $periodo): bool => $periodo->mes <= $hastaMes && ! $periodo->esta_cerrado)
            ->each(fn (Periodo $periodo) => $periodo->cerrar($this->cajera));
    }

    /**
     * Abre enero a mes en curso. Los que ya existen no se tocan.
     *
     * @return Collection<int, Periodo>
     */
    private function abrirPeriodosDelAnio(): Collection
    {
        for ($mes = 1; $mes <= (int) now()->month; $mes++) {
            $inicio = Carbon::create($this->anio, $mes, 1);

            Periodo::firstOrCreate(
                ['anio' => $this->anio, 'mes' => $mes],
                [
                    'fecha_inicio' => $inicio->toDateString(),
                    'fecha_fin' => $inicio->copy()->endOfMonth()->toDateString(),
                ]
            );
        }

        return Periodo::query()
            ->where('anio', $this->anio)
            ->orderBy('mes')
            ->get();
    }

    /**
     * @return Collection<int, Sector>
     */
    private function asegurarSectores(): Collection
    {
        foreach (['Centro', 'El Porvenir', 'San Antonio', 'Las Flores'] as $orden => $nombre) {
            Sector::firstOrCreate(
                ['nombre' => $nombre],
                ['orden' => $orden + 1, 'activo' => true]
            );
        }

        return Sector::query()->activos()->get();
    }

    /**
     * Garantiza tarifa desde enero, con un alza en julio para que el año tenga
     * dos vigencias y se vea que cada boleta guardó la que le tocaba.
     *
     * @return Collection<int, Paja>
     */
    private function asegurarTarifas(): Collection
    {
        $pajas = Paja::query()->where('activo', true)->get();

        if ($pajas->isEmpty()) {
            throw new RuntimeException('No hay pajas configuradas: corré primero «php artisan db:seed».');
        }

        foreach ($pajas as $paja) {
            $base = round((float) $paja->equivalencia_m3 * 1.5, 2);

            $vigencias = [
                [$this->anio.'-01-01', $base, 2.5000],
                [$this->anio.'-07-01', round($base * 1.12, 2), 2.7500],
            ];

            foreach ($vigencias as [$desde, $monto, $excedente]) {
                // Se compara con whereDate y no con firstOrCreate: el cast
                // `date` guarda la hora en cero, y buscar por 'YYYY-MM-DD' a
                // secas no vuelve a encontrar la fila en SQLite.
                $yaExiste = Tarifa::query()
                    ->where('paja_id', $paja->id)
                    ->whereDate('vigente_desde', $desde)
                    ->exists();

                if ($yaExiste) {
                    continue;
                }

                Tarifa::create([
                    'paja_id' => $paja->id,
                    'vigente_desde' => $desde,
                    'monto_base' => $monto,
                    'precio_m3_excedente' => $excedente,
                ]);
            }
        }

        return $pajas;
    }

    /**
     * Borra lo sembrado en la corrida anterior.
     *
     * Va por consultas directas y no por Eloquent a propósito: los observers
     * impiden borrar boletas y pagos —y con razón, son documentos contables—,
     * pero estos nunca existieron fuera de la base de prueba.
     */
    private function limpiarSiembraAnterior(): void
    {
        $clientes = Cliente::withTrashed()
            ->where('codigo', 'like', self::PREFIJO_CLIENTE.'%')
            ->pluck('id');

        if ($clientes->isEmpty()) {
            return;
        }

        $contadores = Contador::withTrashed()->whereIn('cliente_id', $clientes)->pluck('id');
        $predios = DB::table('contadores')->whereIn('id', $contadores)->pluck('predio_id');
        $lecturas = DB::table('lecturas')->whereIn('contador_id', $contadores)->pluck('id');
        $boletas = DB::table('boletas')->whereIn('cliente_id', $clientes)->pluck('id');
        $pagos = DB::table('pagos')->whereIn('boleta_id', $boletas)->pluck('id');

        DB::table('pagos')->whereIn('id', $pagos)->delete();
        DB::table('boletas')->whereIn('id', $boletas)->delete();
        DB::table('evidencias_lectura')->whereIn('lectura_id', $lecturas)->delete();
        DB::table('lecturas')->whereIn('id', $lecturas)->delete();
        DB::table('contadores')->whereIn('id', $contadores)->delete();
        DB::table('documentos')->whereIn('cliente_id', $clientes)->delete();
        DB::table('cliente_accesos')->whereIn('cliente_id', $clientes)->delete();
        DB::table('clientes')->whereIn('id', $clientes)->delete();

        // Los predios se comparten: solo se van los que quedaron sin medidor.
        DB::table('predios')
            ->whereIn('id', $predios)
            ->whereNotExists(fn ($consulta) => $consulta
                ->select(DB::raw(1))
                ->from('contadores')
                ->whereColumn('contadores.predio_id', 'predios.id'))
            ->delete();

        $this->borrarAuditorias([
            Cliente::class => $clientes,
            Contador::class => $contadores,
            Predio::class => $predios,
            Lectura::class => $lecturas,
            Boleta::class => $boletas,
            Pago::class => $pagos,
        ]);

        $this->reabrirPeriodosVacios();
    }

    /**
     * La bitácora de algo que ya no existe solo estorba en la pantalla de
     * auditoría.
     *
     * @param  array<class-string, \Illuminate\Support\Collection<int, int>>  $porModelo
     */
    private function borrarAuditorias(array $porModelo): void
    {
        foreach ($porModelo as $modelo => $ids) {
            if ($ids->isEmpty()) {
                continue;
            }

            DB::table('audits')
                ->where('auditable_type', $modelo)
                ->whereIn('auditable_id', $ids)
                ->delete();
        }
    }

    /**
     * Un mes que se cerró en la corrida anterior y quedó sin nada adentro
     * vuelve a estar abierto; de lo contrario el seeder no se podría repetir.
     */
    private function reabrirPeriodosVacios(): void
    {
        Periodo::query()
            ->whereNotNull('cerrado_en')
            ->whereDoesntHave('lecturas')
            ->whereDoesntHave('boletas')
            ->update(['cerrado_en' => null, 'cerrado_por' => null]);
    }

    private function usuarioDeVentanilla(): User
    {
        $usuario = User::query()->where('email', config('admin.email'))->first()
            ?? User::query()->orderBy('id')->first();

        if ($usuario === null) {
            throw new RuntimeException('No hay usuarios: corré primero «php artisan db:seed».');
        }

        return $usuario;
    }

    private function usuarioLector(): User
    {
        return User::query()
            ->whereHas('roles', fn ($consulta) => $consulta->where('name', 'Lector'))
            ->first() ?? $this->cajera;
    }

    private function nombreDeVecino(int $numero): string
    {
        $nombres = [
            'María Xitumul', 'Juan Pérez Cux', 'Rosa Ixcoy', 'Pedro Tzunún',
            'Ana Lucía Morales', 'Carlos Batz', 'Elena Chávez', 'José Sicán',
            'Marta Yaxón', 'Diego Ramírez', 'Silvia Cotzajay', 'Luis Toj',
            'Claudia Mejía', 'Óscar Patzán', 'Norma Xocop', 'Byron Saquic',
            'Gladys Tucux', 'Efraín Chuc', 'Verónica Quim', 'Mario Ajcalón',
            'Dora Chumil', 'Hugo Semeyá', 'Irma Colaj', 'Rolando Tepaz',
            'Sandra Guarcax', 'Emilio Bocel',
        ];

        return $nombres[($numero - 1) % count($nombres)];
    }

    /**
     * @param  array<string, string>  $casos
     */
    private function mostrarResumen(array $casos): void
    {
        if ($this->command === null) {
            return;
        }

        $filas = Cliente::query()
            ->select('clientes.*')
            ->conEstadoDeCuenta()
            ->withCount('boletas')
            ->where('codigo', 'like', self::PREFIJO_CLIENTE.'%')
            ->orderBy('codigo')
            ->get()
            ->map(fn (Cliente $cliente): array => [
                $cliente->codigo,
                $cliente->nombre,
                $casos[$cliente->codigo] ?? '',
                $cliente->boletas_count,
                number_format($cliente->deuda_total, 2),
                $cliente->estado_de_cuenta,
            ])
            ->all();

        $this->command->table(
            ['Código', 'Vecino', 'Caso', 'Boletas', 'Deuda', 'Estado'],
            $filas
        );

        $this->command->info(sprintf(
            'Sembrados %d vecinos, %d boletas y %d pagos del %d.',
            count($filas),
            Boleta::query()->whereIn('cliente_id', Cliente::query()->where('codigo', 'like', self::PREFIJO_CLIENTE.'%')->select('id'))->count(),
            Pago::query()->whereIn('boleta_id', Boleta::query()->whereIn('cliente_id', Cliente::query()->where('codigo', 'like', self::PREFIJO_CLIENTE.'%')->select('id'))->select('id'))->count(),
            $this->anio,
        ));
    }
}
