<?php

namespace App\Filament\Admin\Pages;

use App\Enums\FormatoPapel;
use App\Filament\Admin\Enums\GrupoNavegacion;
use App\Models\Configuracion as Ajuste;
use App\Support\AjustesDeImpresion;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Lo que cada oficina ajusta sin tocar código.
 *
 * El sistema es genérico a propósito: la misma instalación sirve a una oficina
 * que imprime en rollo térmico y a otra que imprime en oficio, y cada una le
 * dice a las cosas como les dice su gente. Todo esto vivía clavado en las
 * plantillas o solo editable desde la base de datos.
 */
class Configuracion extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = GrupoNavegacion::Administracion;

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Configuración';

    protected static ?string $slug = 'configuracion';

    protected string $view = 'filament.admin.pages.configuracion';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Configuración';
    }

    public function mount(): void
    {
        $this->form->fill($this->valoresGuardados());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Datos de la entidad')
                    ->description('Lo que sale en el encabezado de todo documento impreso.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('entidad.nombre')
                            ->label('Nombre de la entidad')
                            ->required()
                            ->maxLength(150),

                        TextInput::make('entidad.nit')
                            ->label('NIT')
                            ->maxLength(20),

                        TextInput::make('entidad.direccion')
                            ->label('Dirección de la oficina')
                            ->maxLength(255),

                        TextInput::make('entidad.telefono')
                            ->label('Teléfono')
                            ->maxLength(20),

                        TextInput::make('ubicacion.municipio')
                            ->label('Municipio')
                            ->maxLength(100),

                        TextInput::make('ubicacion.departamento')
                            ->label('Departamento')
                            ->maxLength(100),
                    ]),

                Section::make('Impresión')
                    ->description('Cómo salen las boletas y los recibos en papel.')
                    ->columns(2)
                    ->schema([
                        Select::make('impresion.formato')
                            ->label('Formato de papel')
                            ->required()
                            ->native(false)
                            ->options(FormatoPapel::opciones())
                            ->default(FormatoPapel::Termica80->value)
                            ->helperText('Un documento maquetado para rollo de 80 mm sale ilegible en A4, y al revés.'),

                        Toggle::make('impresion.mostrar_logo')
                            ->label('Imprimir el logotipo')
                            ->live()
                            ->helperText('Ocupa espacio en el rollo térmico; en hoja completa casi no se nota.'),

                        FileUpload::make('entidad.logo')
                            ->label('Logotipo')
                            ->image()
                            ->disk('local')
                            ->directory('configuracion')
                            ->visibility('private')
                            ->maxSize(2048)
                            ->imagePreviewHeight('120')
                            ->visible(fn (Get $get): bool => (bool) $get('impresion.mostrar_logo'))
                            ->columnSpanFull()
                            ->validationMessages(['max' => 'El logotipo no puede pasar de 2 MB.'])
                            ->helperText('Se incrusta en el documento al imprimir. Preferible fondo transparente o blanco.'),

                        Textarea::make('impresion.pie_de_pagina')
                            ->label('Pie de página')
                            ->rows(2)
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Ej.: «Conserve este comprobante» o el horario de atención de la oficina.'),
                    ]),

                Section::make('Nombres de los conceptos')
                    ->description('Cómo se llama cada cosa en el papel. Déjelo vacío para usar el nombre de fábrica que se muestra como guía.')
                    ->columns(2)
                    ->collapsed()
                    ->schema(static::camposDeEtiquetas()),

                Section::make('Facturación')
                    ->columns(2)
                    ->schema([
                        TextInput::make('facturacion.dias_vencimiento')
                            ->label('Días para el vencimiento')
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(365)
                            ->default(30)
                            ->helperText('Cuántos días tiene el vecino para pagar desde que se emite la boleta.'),

                        Toggle::make('facturacion.emitir_al_registrar')
                            ->label('Emitir la boleta al registrar la lectura')
                            ->helperText('Apagado, la oficina revisa la ruta y después emite todas juntas. Encendido, cada lectura genera su boleta en el acto — cómodo, pero corregir una lectura ya facturada obliga a anular la boleta y quemar un folio.'),
                    ]),
            ]);
    }

    /**
     * Un campo por concepto, con el nombre de fábrica de marcador.
     *
     * @return array<int, TextInput>
     */
    protected static function camposDeEtiquetas(): array
    {
        return collect(AjustesDeImpresion::ETIQUETAS)
            ->map(fn (string $porDefecto, string $clave): TextInput => TextInput::make("etiqueta.{$clave}")
                ->label(static::nombreDelConcepto($clave))
                ->placeholder($porDefecto)
                ->maxLength(120))
            ->values()
            ->all();
    }

    protected static function nombreDelConcepto(string $clave): string
    {
        return match ($clave) {
            'recibo.titulo_cobro' => 'Título del documento de cobro',
            'recibo.titulo_pago' => 'Título del recibo de pago',
            'recibo.contribuyente' => 'Cómo se llama al titular',
            'recibo.servicio' => 'Cómo se llama al servicio',
            'recibo.contador' => 'Cómo se llama al medidor',
            'recibo.canon' => 'Cuota fija',
            'recibo.excedente' => 'Excedente de consumo',
            'recibo.lecturas' => 'Encabezado de lecturas',
            'recibo.consumo' => 'Consumo',
            'recibo.total' => 'Total',
            'recibo.saldo' => 'Saldo pendiente',
            'recibo.saldado' => 'Sello de saldado',
            'recibo.recibi' => 'Encabezado del monto recibido',
            default => $clave,
        };
    }

    public function guardar(): void
    {
        $datos = $this->form->getState();

        foreach ($this->aplanar($datos) as $clave => $valor) {
            Ajuste::guardar($clave, $this->normalizar($valor));
        }

        Notification::make()
            ->success()
            ->title('Configuración guardada')
            ->body('Los documentos que se impriman desde ahora usan estos ajustes.')
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('guardar')
                ->label('Guardar cambios')
                ->submit('guardar'),
        ];
    }

    /**
     * Lo guardado hoy, con la forma anidada que espera el formulario.
     *
     * @return array<string, mixed>
     */
    protected function valoresGuardados(): array
    {
        $valores = [];

        foreach ($this->clavesConocidas() as $clave) {
            data_set($valores, $clave, Ajuste::obtener($clave));
        }

        return $valores;
    }

    /**
     * @return array<int, string>
     */
    protected function clavesConocidas(): array
    {
        return [
            'entidad.nombre', 'entidad.nit', 'entidad.direccion', 'entidad.telefono', 'entidad.logo',
            'ubicacion.municipio', 'ubicacion.departamento',
            'impresion.formato', 'impresion.mostrar_logo', 'impresion.pie_de_pagina',
            'facturacion.dias_vencimiento', 'facturacion.emitir_al_registrar',
            ...array_map(fn (string $clave): string => "etiqueta.{$clave}", array_keys(AjustesDeImpresion::ETIQUETAS)),
        ];
    }

    /**
     * El formulario devuelve el estado anidado; `configuracion` guarda claves
     * con punto.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    protected function aplanar(array $datos, string $prefijo = ''): array
    {
        $plano = [];

        foreach ($datos as $clave => $valor) {
            $completa = $prefijo === '' ? (string) $clave : "{$prefijo}.{$clave}";

            if (is_array($valor) && ! array_is_list($valor)) {
                $plano += $this->aplanar($valor, $completa);

                continue;
            }

            $plano[$completa] = $valor;
        }

        return $plano;
    }

    /**
     * La tabla guarda texto: un toggle tiene que quedar como '1' o '0', y el
     * archivo subido llega como lista de una sola ruta.
     */
    protected function normalizar(mixed $valor): ?string
    {
        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        if (is_array($valor)) {
            $primero = reset($valor);

            return $primero === false ? null : (string) $primero;
        }

        return blank($valor) ? null : (string) $valor;
    }
}
