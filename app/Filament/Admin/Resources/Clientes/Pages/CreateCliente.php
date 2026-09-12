<?php

namespace App\Filament\Admin\Resources\Clientes\Pages;

use App\Filament\Admin\Resources\Clientes\ClienteResource;
use App\Filament\Admin\Resources\Clientes\Schemas\ClienteForm;
use App\Filament\Admin\Resources\Contadores\Schemas\ContadorForm;
use App\Filament\Admin\Resources\Documentos\Schemas\DocumentoForm;
use App\Filament\Admin\Resources\Predios\Schemas\PredioForm;
use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Predio;
use App\Services\ArchivadorDeExpediente;
use Filament\Forms\Components\Hidden;
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
use Illuminate\Support\Str;

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
                ->schema([
                    // Identifica el intento de alta, no al cliente. Viaja con
                    // el formulario para que reenviarlo no cree dos personas.
                    Hidden::make('token_alta')
                        ->default(fn (): string => (string) Str::uuid()),

                    ...ClienteForm::campos(),

                    Group::make(DocumentoForm::camposAdjuntos(respaldaPredio: false))
                        ->statePath('documento_persona'),
                ]),

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

                    // Sin propiedad no hay nada que respaldar, así que el
                    // adjunto desaparece junto con el resto del paso.
                    Group::make(DocumentoForm::camposAdjuntos(respaldaPredio: true))
                        ->statePath('documento_predio')
                        ->visible(fn (Get $get): bool => $get('modo_predio') !== 'ninguno'),
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
     * Corta antes de validar si este mismo intento ya quedó registrado.
     *
     * Tiene que ser acá y no en el guardado: reenviar el formulario trae el
     * código que ya se usó, así que la regla `unique` lo rechazaría primero y
     * el usuario vería «ese código ya existe» en lugar de enterarse de que su
     * alta sí había funcionado.
     */
    protected function beforeValidate(): void
    {
        $yaCreado = $this->clientePorToken($this->data['token_alta'] ?? null);

        if ($yaCreado === null) {
            return;
        }

        Notification::make()
            ->warning()
            ->title('Este alta ya se había registrado')
            ->body("{$yaCreado->nombre} quedó dado de alta con el código {$yaCreado->codigo}. No se creó por duplicado.")
            ->send();

        $this->redirect($this->getResource()::getUrl('edit', ['record' => $yaCreado]));

        $this->halt();
    }

    /**
     * El cliente que produjo este intento de alta, si ya existe.
     */
    private function clientePorToken(?string $token): ?Cliente
    {
        return blank($token)
            ? null
            : Cliente::where('token_alta', $token)->first();
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
        $documentoPersona = $data['documento_persona'] ?? null;
        $documentoPredio = $data['documento_predio'] ?? null;

        $datosCliente = collect($data)
            ->except([
                'modo_predio',
                'predio',
                'contador',
                'predio_existente_id',
                'documento_persona',
                'documento_predio',
            ])
            ->all();

        $archivador = app(ArchivadorDeExpediente::class);

        return DB::transaction(function () use (
            $modo,
            $datosCliente,
            $datosPredio,
            $datosContador,
            $predioExistente,
            $documentoPersona,
            $documentoPredio,
            $archivador,
        ): Cliente {
            // Segunda línea, para dos peticiones realmente simultáneas: la de
            // `beforeValidate()` puede haber mirado antes de que la otra
            // insertara. El índice único de `token_alta` es el que cierra el
            // caso extremo en que ni siquiera esta llega a tiempo.
            $yaCreado = $this->clientePorToken($datosCliente['token_alta'] ?? null);

            if ($yaCreado !== null) {
                return $yaCreado;
            }

            $cliente = Cliente::create($datosCliente);

            // El DPI documenta a la persona: no depende de que haya servicio.
            $archivador->adjuntar($cliente, $documentoPersona);

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

            $archivador->adjuntar($cliente, $documentoPredio, $predioId);

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
