<?php

namespace App\Filament\Lector\Pages;

use App\Filament\Admin\Resources\Lecturas\Schemas\LecturaForm;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Periodo;
use App\Services\EmisorAutomatico;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * La pantalla del lector en campo.
 *
 * No es un CRUD de contadores: es la lista de los que todavía no se han leído
 * en el período, ordenada como se camina la ruta, con el registro reducido a
 * teclear una cifra. El README lo marca como el problema principal a resolver,
 * y se usa desde el navegador del celular.
 */
class RutaDeLectura extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Mi ruta';

    protected static ?string $slug = 'ruta';

    /**
     * La ruta es la pantalla de inicio del panel: el lector entra y ya está en
     * su trabajo, sin un tablero de por medio.
     */
    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    protected string $view = 'filament.lector.pages.ruta-de-lectura';

    /**
     * Período sobre el que se está recorriendo la ruta.
     */
    public ?int $periodoId = null;

    /**
     * Pestaña activa: 'pendientes' o 'leidos'.
     */
    public string $vista = 'pendientes';

    public function getTitle(): string
    {
        return '';
    }

    public function mount(): void
    {
        $this->periodoId = static::periodoPorDefecto()?->id;
    }

    protected static function periodoPorDefecto(): ?Periodo
    {
        return Periodo::vigente();
    }

    public function getPeriodo(): ?Periodo
    {
        return $this->periodoId ? Periodo::find($this->periodoId) : null;
    }

    /**
     * Los períodos abiertos entre los que puede elegir el lector.
     *
     * @return array<int, string>
     */
    public function getPeriodosDisponiblesProperty(): array
    {
        return Periodo::abiertos()
            ->orderByDesc('fecha_inicio')
            ->get()
            ->pluck('etiqueta', 'id')
            ->all();
    }

    /**
     * Registrar una lectura exige que hoy caiga dentro del período: es la misma
     * regla del formulario largo, y aquí la fecha no se pregunta.
     */
    public function puedeRegistrar(): bool
    {
        $periodo = $this->getPeriodo();

        return $periodo !== null
            && now()->startOfDay()->betweenIncluded($periodo->fecha_inicio, $periodo->fecha_fin);
    }

    public function getAvisoProperty(): ?string
    {
        $periodo = $this->getPeriodo();

        if ($periodo === null) {
            return 'No hay ningún período abierto. Avise a la oficina para que abran el ciclo del mes.';
        }

        if (! $this->puedeRegistrar()) {
            return sprintf(
                'Hoy (%s) está fuera del período %s, que va del %s al %s. Puede consultar la ruta, pero para registrar visitas avise a la oficina.',
                now()->format('d/m/Y'),
                $periodo->etiqueta,
                $periodo->fecha_inicio->format('d/m/Y'),
                $periodo->fecha_fin->format('d/m/Y'),
            );
        }

        return null;
    }

    /**
     * @return array{leidos: int, total: int}
     */
    public function getAvanceProperty(): array
    {
        $periodo = $this->getPeriodo();

        if ($periodo === null) {
            return ['leidos' => 0, 'total' => 0];
        }

        $suyos = Contador::query()->activos()->deLaRutaDe(auth()->user());

        return [
            // Su recorrido, no el del padrón entero: a un lector con la mitad
            // de los sectores le diría que va por la mitad para siempre.
            'leidos' => (clone $suyos)->whereHas(
                'lecturas',
                fn (Builder $q): Builder => $q->where('periodo_id', $periodo->id)
            )->count(),
            'total' => $suyos->count(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->consultaBase())
            ->columns([
                TextColumn::make('codigo')
                    ->label('Contador')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (Contador $record): ?string => $record->cliente?->nombre),

                TextColumn::make('predio.direccion_completa')
                    ->label('Dirección')
                    ->placeholder('Sin dirección registrada')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'predio',
                        fn (Builder $predio): Builder => $predio
                            ->where('calle', 'like', "%{$search}%")
                            ->orWhere('aldea', 'like', "%{$search}%")
                            ->orWhere('numero_casa', 'like', "%{$search}%")
                    )),

                // En el celular solo caben las tres primeras: el resto es
                // contexto de oficina, no de campo.
                TextColumn::make('predio.sector.nombre')
                    ->label('Sector')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Sin sector')
                    ->visibleFrom('md'),

                TextColumn::make('paja.nombre')
                    ->label('Paja')
                    ->badge()
                    ->color('info')
                    ->visibleFrom('lg'),

                TextColumn::make('ultima_lectura')
                    ->label('Última lectura')
                    ->state(fn (Contador $record): string => number_format(
                        (float) ($record->ultimaLectura()?->lectura_actual ?? 0),
                        2
                    ).' m³')
                    ->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('sector')
                    ->label('Sector')
                    ->relationship('predio.sector', 'nombre')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                $this->accionRegistrarLectura(),
                $this->accionCorregirLectura(),
            ])
            ->toolbarActions([])
            ->emptyStateHeading(fn (): string => $this->vista === 'pendientes'
                ? 'No queda ningún contador pendiente'
                : 'Todavía no hay lecturas en este período')
            ->emptyStateDescription(fn (): ?string => $this->vista === 'pendientes'
                ? 'La ruta de este período está completa.'
                : null);
    }

    /**
     * Se camina como se recorre: primero el sector que va antes en la ruta y
     * dentro de él por dirección. Para eso existe `sectores.orden`.
     */
    protected function consultaBase(): Builder
    {
        $periodo = $this->getPeriodo();

        $consulta = Contador::query()
            // Cada lector camina lo suyo. Sin sectores asignados ve todo, que
            // es el caso de la oficina con un solo lector.
            ->deLaRutaDe(auth()->user())
            ->with(['cliente', 'predio.sector', 'paja'])
            ->leftJoin('predios', 'predios.id', '=', 'contadores.predio_id')
            ->leftJoin('sectores', 'sectores.id', '=', 'predios.sector_id')
            ->select('contadores.*')
            ->orderBy('sectores.orden')
            ->orderBy('predios.aldea')
            ->orderBy('predios.numero_casa');

        if ($periodo === null) {
            return $consulta->whereRaw('1 = 0');
        }

        return $this->vista === 'pendientes'
            ? $consulta->sinLecturaEn($periodo->id)
            : $consulta->whereHas('lecturas', fn (Builder $q): Builder => $q->where('periodo_id', $periodo->id));
    }

    protected function accionRegistrarLectura(): Action
    {
        return Action::make('registrar')
            ->label('Registrar')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->visible(fn (): bool => $this->vista === 'pendientes' && $this->puedeRegistrar())
            ->modalHeading(fn (Contador $record): string => "Contador {$record->codigo}")
            ->modalDescription(fn (Contador $record): string => $record->predio?->direccion_completa ?: 'Sin dirección registrada')
            ->modalSubmitActionLabel('Guardar lectura')
            ->fillForm(fn (Contador $record): array => [
                'lectura_anterior' => (float) ($record->ultimaLectura()?->lectura_actual ?? 0),
            ])
            ->schema([
                ...LecturaForm::camposDelMarcador(),

                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->maxLength(255)
                    ->rows(2)
                    ->helperText('Ej.: medidor empañado, portón cerrado, fuga visible.'),
            ])
            ->action(function (Contador $record, array $data): void {
                $lectura = Lectura::create([
                    'contador_id' => $record->id,
                    'periodo_id' => $this->periodoId,
                    'usuario_id' => auth()->id(),
                    'lectura_anterior' => $data['lectura_anterior'],
                    'lectura_actual' => $data['lectura_actual'],
                    'fecha_lectura' => now()->toDateString(),
                    'observaciones' => $data['observaciones'] ?? null,
                ]);

                Notification::make()
                    ->success()
                    ->title("Contador {$record->codigo} leído")
                    ->body("Consumo del período: {$lectura->refresh()->consumo_m3} m³.")
                    ->send();

                app(EmisorAutomatico::class)->emitirSiCorresponde($lectura);
            });
    }

    /**
     * Corregir lo que se acaba de escribir, sin volver a la oficina.
     *
     * Un dedazo en el celular es la cosa más previsible de esta pantalla. El
     * lector ya no entra a /admin, así que si no puede arreglarlo acá tiene que
     * pedirle a alguien que lo haga, y hasta entonces la boleta sale mal.
     *
     * Solo mientras la lectura no esté facturada: después ya es el respaldo de
     * un documento contable y corregirla obliga a anular la boleta.
     */
    protected function accionCorregirLectura(): Action
    {
        return Action::make('corregir')
            ->label('Corregir')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('gray')
            ->visible(fn (): bool => $this->vista === 'leidos')
            // Deshabilitado y no escondido: si el botón desaparece, el lector
            // se queda preguntándose por qué esta casa sí y aquella no.
            ->disabled(fn (Contador $record): bool => $this->lecturaDelPeriodo($record)?->esta_facturada ?? true)
            ->tooltip(fn (Contador $record): ?string => ($this->lecturaDelPeriodo($record)?->esta_facturada ?? true)
                ? 'Ya se facturó. Avise a la oficina si hay que corregirla.'
                : null)
            ->modalHeading(fn (Contador $record): string => "Contador {$record->codigo}")
            ->modalDescription(fn (Contador $record): string => $record->predio?->direccion_completa ?: 'Sin dirección registrada')
            ->modalSubmitActionLabel('Guardar la corrección')
            ->fillForm(fn (Contador $record): array => [
                'lectura_anterior' => (float) $this->lecturaDelPeriodo($record)?->lectura_anterior,
                'lectura_actual' => (float) $this->lecturaDelPeriodo($record)?->lectura_actual,
                'observaciones' => $this->lecturaDelPeriodo($record)?->observaciones,
            ])
            ->schema([
                ...LecturaForm::camposDelMarcador(),

                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->maxLength(255)
                    ->rows(2),
            ])
            ->action(function (Contador $record, array $data): void {
                $lectura = $this->lecturaDelPeriodo($record);

                if ($lectura === null || $lectura->esta_facturada) {
                    Notification::make()
                        ->danger()
                        ->title('Ya no se puede corregir')
                        ->body('Esta lectura fue facturada. Avise a la oficina para que anulen la boleta.')
                        ->persistent()
                        ->send();

                    return;
                }

                $lectura->update([
                    'lectura_actual' => $data['lectura_actual'],
                    'observaciones' => $data['observaciones'] ?? null,
                ]);

                Notification::make()
                    ->success()
                    ->title("Contador {$record->codigo} corregido")
                    ->body("Consumo del período: {$lectura->refresh()->consumo_m3} m³.")
                    ->send();
            });
    }

    protected function lecturaDelPeriodo(Contador $contador): ?Lectura
    {
        return $contador->lecturas()->where('periodo_id', $this->periodoId)->first();
    }

    public function cambiarVista(string $vista): void
    {
        $this->vista = $vista;
        $this->resetTable();
    }

    public function updatedPeriodoId(): void
    {
        $this->resetTable();
    }

    public static function getNavigationBadge(): ?string
    {
        $periodo = static::periodoPorDefecto();

        if ($periodo === null) {
            return null;
        }

        $pendientes = Contador::query()->sinLecturaEn($periodo->id)->count();

        return $pendientes > 0 ? (string) $pendientes : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Contadores pendientes de lectura';
    }
}
