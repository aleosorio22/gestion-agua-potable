<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Boletas\BoletaResource;
use App\Filament\Admin\Resources\Clientes\ClienteResource;
use App\Models\Cliente;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Quién debe y cuánto: la consulta con la que se atiende en ventanilla.
 *
 * El vecino llega y pregunta si está al día. La secretaria lo busca por lo que
 * traiga a mano —su código, su nombre, su DPI— y ve el saldo de todos sus
 * servicios juntos, sin abrir boleta por boleta.
 */
class EstadoDeCuentaClientes extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Estado de cuenta')
            ->description('Lo que cada vecino debe, sumando todos sus servicios.')
            ->query(
                // El saldo se deriva de los pagos y no es columna: sin estas
                // subconsultas habría una consulta por cliente y otra por
                // boleta, y con el padrón lleno se nota.
                Cliente::query()->conEstadoDeCuenta()->withCount('contadores')
            )
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Código copiado'),

                TextColumn::make('nombre')
                    ->label('Cliente')
                    ->description(fn (Cliente $record): ?string => $record->telefono)
                    // Lo que el vecino trae a mano en ventanilla.
                    ->searchable(['nombre', 'dpi', 'nit'])
                    ->sortable(),

                TextColumn::make('contadores_count')
                    ->label('Servicios')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('deuda')
                    ->label('Debe')
                    ->alignEnd()
                    ->weight('medium')
                    ->formatStateUsing(fn ($state): string => 'Q'.number_format((float) $state, 2))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('deuda', $direction)),

                TextColumn::make('vence_mas_antigua')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->description(fn (Cliente $record): ?string => $record->estado_de_cuenta === 'vencido'
                        ? 'La más atrasada'
                        : null)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('vence_mas_antigua', $direction)),

                TextColumn::make('estado_de_cuenta')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'al_dia' => 'Al día',
                        'vencido' => 'Vencido',
                        default => 'Pendiente',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'al_dia' => 'success',
                        'vencido' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->defaultSort('deuda', 'desc')
            ->filters([
                SelectFilter::make('estado_de_cuenta')
                    ->label('Estado de cuenta')
                    ->options([
                        'vencido' => 'Vencido',
                        'pendiente' => 'Pendiente',
                        'al_dia' => 'Al día',
                    ])
                    // El estado no es columna: se reproduce con las mismas
                    // condiciones que lo derivan.
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'al_dia' => $query->whereDoesntHave('boletas', fn (Builder $b) => $b->pendientes()),
                        'pendiente' => $query
                            ->whereHas('boletas', fn (Builder $b) => $b->pendientes())
                            ->whereDoesntHave('boletas', fn (Builder $b) => $b->vencidas()),
                        'vencido' => $query->whereHas('boletas', fn (Builder $b) => $b->vencidas()),
                        default => $query,
                    }),

                SelectFilter::make('sector')
                    ->label('Sector')
                    ->relationship('predios.sector', 'nombre')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('boletas')
                    ->label('Ver boletas')
                    ->icon(Heroicon::OutlinedDocumentCurrencyDollar)
                    ->color('gray')
                    ->url(fn (Cliente $record): string => BoletaResource::getUrl('index', [
                        'tableSearch' => $record->codigo,
                    ])),

                Action::make('ficha')
                    ->label('Ficha')
                    ->icon(Heroicon::OutlinedUser)
                    ->color('gray')
                    ->url(fn (Cliente $record): string => ClienteResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Todavía no hay clientes en el padrón')
            ->emptyStateDescription('Registre al primer vecino para empezar a ver su estado de cuenta.');
    }
}
