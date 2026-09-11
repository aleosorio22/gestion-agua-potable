<?php

namespace App\Filament\Admin\Resources\Usuarios\Tables;

use App\Filament\Admin\Support\AccionesUsuario;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UsuariosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->description(fn (User $record): string => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Sin rol asignado'),

                IconColumn::make('activo')
                    ->label('Activa')
                    ->boolean(),

                // Es lo que decide si la cuenta se puede borrar o solo dar de
                // baja: las claves foráneas la retienen en cuanto hay trabajo.
                TextColumn::make('lecturas_count')
                    ->label('Lecturas')
                    ->counts('lecturas')
                    ->sortable(),

                TextColumn::make('pagos_count')
                    ->label('Pagos')
                    ->counts('pagos')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('roles'))
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('rol')
                    ->label('Rol')
                    ->options(fn (): array => Role::query()->orderBy('name')->pluck('name', 'name')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('roles', fn (Builder $rol) => $rol->where('name', $data['value']))
                        : $query),

                TernaryFilter::make('activo')
                    ->label('Cuenta activa'),
            ])
            ->recordActions([
                EditAction::make(),
                AccionesUsuario::restablecerContrasena(),
                AccionesUsuario::alternarActivo(),
                AccionesUsuario::eliminar(),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('No hay usuarios registrados')
            ->emptyStateDescription('Cree las cuentas de la secretaria y del lector para que puedan trabajar.');
    }
}
