<?php

namespace App\Filament\Admin\Resources\Contadores\Schemas;

use App\Filament\Admin\Resources\Documentos\Schemas\DocumentoForm;
use App\Filament\Admin\Resources\Predios\Schemas\PredioForm;
use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Paja;
use App\Models\Predio;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Los campos van sueltos y no dentro de un único array porque este formulario
 * se monta de tres formas distintas: completo en su propio Resource, sin
 * titular dentro de la ficha del cliente, y sin titular ni predio dentro del
 * alta guiada, donde ambos ya vienen de los pasos anteriores.
 *
 * Los selectores van por `options()` y no por `relationship()`: fuera del
 * Resource el modelo del formulario es otro y la relación no existe ahí.
 */
class ContadorForm
{
    public static function configure(Schema $schema, bool $conTitular = true): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->description('El código es con el que el lector ubica el medidor en campo.')
                    ->columns(2)
                    ->schema([
                        static::campoCodigo(),
                        static::campoFechaInstalacion(),
                    ]),

                Section::make('A quién y dónde sirve')
                    ->description('El titular es la persona que paga; el predio es la propiedad donde llega el agua. Pueden no coincidir con la dirección de notificación del cliente.')
                    ->columns(2)
                    ->schema([
                        static::campoTitular()->visible($conTitular)->dehydrated($conTitular),
                        static::campoPredio(),
                        static::campoPaja(),
                    ]),

                Section::make('Situación')
                    ->schema([
                        static::campoEstado(),
                    ]),

                // Solo al conectar: es el momento en que el vecino está en la
                // ventanilla con la escritura. Al editar un contador ya
                // existente el expediente se maneja desde la ficha del cliente.
                Group::make(DocumentoForm::camposAdjuntos(respaldaPredio: true))
                    ->statePath('documento')
                    ->visible(fn (string $operation): bool => $operation === 'create'),
            ]);
    }

    /**
     * Lo propio del aparato: lo que el alta guiada pregunta en su último paso,
     * cuando el titular y el predio ya quedaron definidos.
     *
     * @return array<int, Component>
     */
    public static function camposDelMedidor(): array
    {
        return [
            static::campoCodigo(),
            static::campoPaja(),
            static::campoFechaInstalacion(),
            static::campoEstado(),
        ];
    }

    public static function campoCodigo(): TextInput
    {
        return TextInput::make('codigo')
            ->label('Código del contador')
            ->required()
            ->maxLength(30)
            // La tabla va explícita: dentro del alta guiada el modelo del
            // formulario es Cliente, y sin esto el unique miraría `clientes`.
            ->unique(Contador::class, ignoreRecord: true)
            ->validationMessages([
                // El código de un medidor no se reutiliza: si el que choca es
                // uno dado de baja, lo correcto es restaurarlo, no crear otro
                // registro con el mismo número grabado en el aparato.
                'unique' => 'Ese código ya está registrado en otro contador. Si el contador anterior fue eliminado, restáurelo en lugar de crear uno nuevo.',
            ])
            ->helperText('Como viene grabado en el aparato. Ej.: CTR-00123.');
    }

    public static function campoFechaInstalacion(): DatePicker
    {
        return DatePicker::make('fecha_instalacion')
            ->label('Fecha de instalación')
            ->native(false)
            ->displayFormat('d/m/Y')
            ->maxDate(now())
            ->validationMessages([
                'before_or_equal' => 'La fecha de instalación no puede ser futura.',
            ]);
    }

    public static function campoTitular(): Select
    {
        return Select::make('cliente_id')
            ->label('Titular del servicio')
            ->options(fn (): array => Cliente::query()
                ->activos()
                ->orderBy('nombre')
                ->get()
                ->mapWithKeys(fn (Cliente $cliente): array => [
                    $cliente->id => "{$cliente->codigo} — {$cliente->nombre}",
                ])
                ->all())
            ->searchable()
            ->required()
            ->native(false)
            ->helperText('Solo aparecen los clientes activos.');
    }

    public static function campoPredio(): Select
    {
        return Select::make('predio_id')
            ->label('Predio servido')
            ->options(fn (): array => Predio::query()
                ->orderBy('aldea')
                ->orderBy('numero_casa')
                ->get()
                ->mapWithKeys(fn (Predio $predio): array => [
                    $predio->id => $predio->direccion_completa ?: "Predio #{$predio->getKey()}",
                ])
                ->all())
            ->searchable()
            ->required()
            ->native(false)
            // Alta al vuelo: en ventanilla el predio nuevo aparece junto con el
            // contador, y obligar a salir a otra pantalla para volver es lo que
            // hace que la secretaria termine registrando direcciones a medias.
            ->createOptionUsing(fn (array $data): int => Predio::create($data)->getKey())
            ->createOptionForm(fn (Schema $schema): Schema => PredioForm::configure($schema))
            ->createOptionModalHeading('Nuevo predio');
    }

    public static function campoPaja(): Select
    {
        return Select::make('paja_id')
            ->label('Paja contratada')
            ->options(fn (): array => Paja::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get()
                ->mapWithKeys(fn (Paja $paja): array => [
                    $paja->id => "{$paja->nombre} ({$paja->equivalencia_m3} m³ incluidos)",
                ])
                ->all())
            ->required()
            ->native(false)
            ->helperText('Define cuántos m³ van incluidos antes de que empiece a cobrarse excedente.');
    }

    public static function campoEstado(): Select
    {
        return Select::make('estado')
            ->label('Estado')
            ->required()
            ->native(false)
            ->options([
                'activo' => 'Activo',
                'inactivo' => 'Inactivo',
                'dañado' => 'Dañado',
            ])
            ->default('activo')
            ->helperText('Solo los contadores activos aparecen en la ruta de lectura del período.');
    }
}
