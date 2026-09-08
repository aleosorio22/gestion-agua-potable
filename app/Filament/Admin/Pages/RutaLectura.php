<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Filament\Admin\Resources\Lecturas\Schemas\LecturaForm;
use App\Filament\Admin\Resources\Periodos\PeriodoResource;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Periodo;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * La pantalla del lector en campo.
 *
 * No es un CRUD de contadores: es la lista de los que todavía no se han leído
 * en el período, ordenada como se camina la ruta, con el registro reducido a
 * teclear una cifra. El README lo marca como el problema principal a resolver,
 * y se usa desde el navegador del celular.
 */
class RutaLectura extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Operacion;

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationLabel = 'Ruta de lectura';

    protected static ?string $slug = 'ruta-de-lectura';

    protected string $view = 'filament.admin.pages.ruta-lectura';

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
        return 'Ruta de lectura';
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

    /**
     * Lleva a abrir el ciclo cuando no hay ninguno: el aviso explica el
     * problema, y esto lo resuelve sin hacer buscar la pantalla en el menú.
     */
    public function abrirPeriodoAction(): Action
    {
        return Action::make('abrirPeriodo')
            ->label('Abrir período')
            ->icon(Heroicon::OutlinedCalendarDays)
            ->visible(fn (): bool => $this->getPeriodo() === null)
            ->url(PeriodoResource::getUrl('create'));
    }

    public function getAvisoProperty(): ?string
    {
        $periodo = $this->getPeriodo();

        if ($periodo === null) {
            return 'No hay ningún período abierto. Abra el ciclo del mes para poder recorrer la ruta.';
        }

        if (! $this->puedeRegistrar()) {
            return sprintf(
                'Hoy (%s) está fuera del período %s, que va del %s al %s. Puede consultar la ruta, pero para registrar la visita use la pantalla de Lecturas y ponga la fecha correcta.',
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

        return [
            'leidos' => Lectura::where('periodo_id', $periodo->id)->count(),
            'total' => Contador::activos()->count(),
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
                $this->accionVerLectura(),
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
            });
    }

    protected function accionVerLectura(): Action
    {
        return Action::make('ver')
            ->label('Ver lectura')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->visible(fn (): bool => $this->vista === 'leidos')
            ->url(fn (Contador $record): ?string => ($lectura = $this->lecturaDelPeriodo($record))
                ? route('filament.admin.resources.lecturas.edit', $lectura)
                : null);
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
