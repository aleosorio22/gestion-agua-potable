<?php

namespace App\Filament\Admin\Resources\Clientes\Pages;

use App\Filament\Admin\Resources\Clientes\ClienteResource;
use App\Filament\Admin\Resources\Clientes\Schemas\ClienteForm;
use App\Filament\Admin\Resources\Contadores\Schemas\ContadorForm;
use App\Filament\Admin\Resources\Predios\Schemas\PredioForm;
use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Predio;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Alta guiada: la persona, la propiedad y el medidor en una sola secuencia.
 *
 * Dar de alta un servicio de agua toca tres tablas, y hacerlo en tres pantallas
 * sueltas es lo que produce clientes sin contador y predios sin nadie. El
 * asistente los encadena, pero nada se escribe hasta el último paso: si algo
 * falla a mitad, no queda un cliente huérfano esperando.
 */
class CreateCliente extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = ClienteResource::class;

    public function getTitle(): string
    {
        return 'Nuevo cliente';
    }

    /**
     * @return array<int, Step>
     */
    public function getSteps(): array
    {
        return [
            Step::make('Cliente')
                ->label('La persona')
                ->description('Quién es el titular')
                ->icon(Heroicon::OutlinedUser)
                ->schema(ClienteForm::campos()),

            Step::make('Predio')
                ->label('La propiedad')
                ->description('Dónde llega el agua')
                ->icon(Heroicon::OutlinedHome)
                ->schema([
                    Radio::make('modo_predio')
                        ->label('¿Dónde le llega el agua a esta persona?')
                        ->required()
                        ->live()
                        ->default('nuevo')
                        ->options([
                            'nuevo' => 'Registrar una propiedad nueva',
                            'existente' => 'Usar una propiedad ya registrada',
                            'ninguno' => 'Todavía no tiene servicio',
                        ])
                        ->descriptions([
                            'existente' => 'Para cuando el predio ya está en el sistema, por ejemplo si otro servicio de la misma casa ya fue registrado.',
                            'ninguno' => 'El cliente queda dado de alta y se le puede agregar el servicio más adelante desde su ficha.',
                        ]),

                    Select::make('predio_existente_id')
                        ->label('Propiedad')
                        ->visible(fn (Get $get): bool => $get('modo_predio') === 'existente')
                        ->required(fn (Get $get): bool => $get('modo_predio') === 'existente')
                        ->searchable()
                        ->native(false)
                        ->options(fn (): array => Predio::query()
                            ->orderBy('aldea')
                            ->orderBy('numero_casa')
                            ->get()
                            ->mapWithKeys(fn (Predio $predio): array => [
                                $predio->id => $predio->direccion_completa ?: "Predio #{$predio->getKey()}",
                            ])
                            ->all()),

                    // Anidado bajo `predio` porque `codigo` y `estado` existen
                    // tanto en cliente como en contador: sin separar el estado,
                    // un paso pisaría al otro.
                    Group::make(PredioForm::campos())
                        ->statePath('predio')
                        ->visible(fn (Get $get): bool => $get('modo_predio') === 'nuevo'),
                ]),

            Step::make('Contador')
                ->label('El medidor')
                ->description('Qué aparato mide el consumo')
                ->icon(Heroicon::OutlinedCpuChip)
                ->visible(fn (Get $get): bool => $get('modo_predio') !== 'ninguno')
                ->schema([
                    Group::make(ContadorForm::camposDelMedidor())
                        ->statePath('contador')
                        ->columns(2),
                ]),
        ];
    }

    /**
     * Las tres altas van juntas o no va ninguna.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $modo = $data['modo_predio'] ?? 'ninguno';
        $datosPredio = $data['predio'] ?? [];
        $datosContador = $data['contador'] ?? [];
        $predioExistente = $data['predio_existente_id'] ?? null;

        $datosCliente = collect($data)
            ->except(['modo_predio', 'predio', 'contador', 'predio_existente_id'])
            ->all();

        return DB::transaction(function () use ($modo, $datosCliente, $datosPredio, $datosContador, $predioExistente): Cliente {
            $cliente = Cliente::create($datosCliente);

            if ($modo === 'ninguno') {
                return $cliente;
            }

            $predioId = $modo === 'existente'
                ? $predioExistente
                : Predio::create($datosPredio)->getKey();

            Contador::create([
                ...$datosContador,
                'cliente_id' => $cliente->getKey(),
                'predio_id' => $predioId,
            ]);

            return $cliente;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        $contador = $this->record->contadores()->latest('id')->first();

        return Notification::make()
            ->success()
            ->title('Cliente registrado')
            ->body($contador
                ? "Quedó con el contador {$contador->codigo} instalado y listo para la ruta de lectura."
                : "Ya puede agregarle un contador a {$this->record->nombre} desde esta ficha.");
    }
}
