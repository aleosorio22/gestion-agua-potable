<?php

namespace App\Filament\Admin\Resources\Documentos\Schemas;

use App\Models\Cliente;
use App\Models\Predio;
use App\Models\TipoDocumento;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * El expediente del vecino.
 *
 * La regla que lo ordena está en el catálogo: un tipo con `respalda_predio`
 * documenta una propiedad concreta —escritura, recibo de luz, contrato— y
 * exige decir cuál. Uno sin la bandera documenta a la persona —DPI, NIT— y el
 * predio queda nulo. Sin eso, un contrato quedaría colgando sin decir qué
 * propiedad respalda, que es justamente lo que da valor al expediente.
 */
class DocumentoForm
{
    public static function configure(Schema $schema, bool $conCliente = true): Schema
    {
        return $schema->components(static::campos($conCliente));
    }

    /**
     * @return array<int, Component>
     */
    public static function campos(bool $conCliente = true): array
    {
        return [
            Section::make('Qué documento es')
                ->columns(2)
                ->schema([
                    Select::make('cliente_id')
                        ->label('Titular')
                        ->visible($conCliente)
                        ->dehydrated($conCliente)
                        ->required($conCliente)
                        ->native(false)
                        ->searchable()
                        ->options(fn (): array => Cliente::query()
                            ->orderBy('nombre')
                            ->get()
                            ->mapWithKeys(fn (Cliente $cliente): array => [
                                $cliente->id => "{$cliente->codigo} — {$cliente->nombre}",
                            ])
                            ->all())
                        ->live(),

                    Select::make('tipo_documento_id')
                        ->label('Tipo de documento')
                        ->required()
                        ->native(false)
                        ->live()
                        ->options(fn (): array => TipoDocumento::query()
                            ->where('activo', true)
                            ->orderBy('nombre')
                            ->pluck('nombre', 'id')
                            ->all())
                        ->helperText('Define si el documento respalda a la persona o a una de sus propiedades.'),

                    Select::make('predio_id')
                        ->label('Propiedad que respalda')
                        ->native(false)
                        ->searchable()
                        // Solo aparece para los tipos que documentan una
                        // propiedad, y solo ofrece las del propio titular: una
                        // escritura no puede apuntar a la casa de otro.
                        ->visible(fn (Get $get): bool => static::respaldaPredio($get('tipo_documento_id')))
                        ->required(fn (Get $get): bool => static::respaldaPredio($get('tipo_documento_id')))
                        ->options(fn (Get $get, ?int $state, $livewire): array => static::prediosDelTitular(
                            static::titular($get, $livewire)
                        ))
                        ->validationMessages([
                            'required' => 'Este tipo de documento respalda una propiedad: indique cuál.',
                        ])
                        ->helperText('Solo se ofrecen las propiedades donde este titular tiene servicio.'),
                ]),

            Section::make('Archivo')
                ->description('Foto o escaneo del documento. Se guarda en disco privado y solo se entrega a quien tenga permiso para verlo.')
                ->schema([
                    FileUpload::make('ruta')
                        ->label('Archivo')
                        ->required()
                        ->disk('local')
                        ->directory('documentos')
                        ->visibility('private')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/heic', 'application/pdf'])
                        ->maxSize(10240)
                        ->storeFileNamesIn('nombre_original')
                        ->openable()
                        ->downloadable()
                        ->validationMessages([
                            'max' => 'El archivo no puede pasar de 10 MB. Si es una foto, bájele la resolución.',
                            'mimetypes' => 'Solo se aceptan fotos (JPG, PNG, HEIC) o archivos PDF.',
                        ])
                        ->helperText('Hasta 10 MB. Si le toma foto al papel, procure que se lean los datos.'),
                ]),

            Section::make('Firma')
                ->columns(2)
                ->schema([
                    Toggle::make('firmado')
                        ->label('Está firmado')
                        ->live()
                        ->helperText('Para distinguir un contrato ya firmado de un borrador.'),

                    DatePicker::make('fecha_firma')
                        ->label('Fecha de la firma')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->maxDate(now())
                        ->visible(fn (Get $get): bool => (bool) $get('firmado'))
                        ->validationMessages([
                            'before_or_equal' => 'La fecha de la firma no puede ser futura.',
                        ]),
                ]),
        ];
    }

    /**
     * El adjunto opcional que se ofrece donde el vecino está presente con sus
     * papeles: el alta guiada y la conexión de un servicio.
     *
     * Es el único momento en que la oficina tiene la escritura enfrente. Si no
     * se captura ahí, nadie vuelve a pedirla y el expediente queda vacío.
     *
     * Va opcional a propósito: obligarlo suena prolijo, pero el vecino que
     * llega sin la escritura existe, y un sistema que no lo deja avanzar
     * termina empujando a la secretaria a registrar por fuera.
     *
     * @param  bool  $respaldaPredio  Qué tipos ofrecer: los de la propiedad o los de la persona.
     * @return array<int, Component>
     */
    public static function camposAdjuntos(bool $respaldaPredio): array
    {
        $titulo = $respaldaPredio
            ? 'Documento que respalda la propiedad'
            : 'Documento de identidad';

        $ayuda = $respaldaPredio
            ? 'Escritura, recibo de energía eléctrica o contrato. Puede cargarse después desde el expediente.'
            : 'DPI o constancia de NIT. Puede cargarse después desde el expediente.';

        return [
            Section::make($titulo)
                ->description($ayuda)
                ->columns(2)
                ->collapsible()
                ->schema([
                    Select::make('tipo_documento_id')
                        ->label('Tipo de documento')
                        ->native(false)
                        ->options(fn (): array => TipoDocumento::query()
                            ->where('activo', true)
                            ->where('respalda_predio', $respaldaPredio)
                            ->orderBy('nombre')
                            ->pluck('nombre', 'id')
                            ->all())
                        // Ninguno de los dos es obligatorio por sí solo, pero
                        // un archivo sin tipo no se puede archivar y un tipo
                        // sin archivo no documenta nada.
                        ->required(fn (Get $get): bool => filled($get('ruta')))
                        ->validationMessages([
                            'required' => 'Indique de qué documento se trata.',
                        ]),

                    FileUpload::make('ruta')
                        ->label('Archivo')
                        ->disk('local')
                        ->directory('documentos')
                        ->visibility('private')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/heic', 'application/pdf'])
                        ->maxSize(10240)
                        ->storeFileNamesIn('nombre_original')
                        ->openable()
                        ->required(fn (Get $get): bool => filled($get('tipo_documento_id')))
                        ->validationMessages([
                            'required' => 'Eligió un tipo de documento: adjunte el archivo o quite el tipo.',
                            'max' => 'El archivo no puede pasar de 10 MB. Si es una foto, bájele la resolución.',
                        ])
                        ->helperText('Foto o PDF, hasta 10 MB.'),
                ]),
        ];
    }

    private static function respaldaPredio(mixed $tipoId): bool
    {
        return $tipoId
            ? (bool) TipoDocumento::whereKey($tipoId)->value('respalda_predio')
            : false;
    }

    /**
     * El titular sale del formulario, o del cliente cuya ficha está abierta.
     */
    private static function titular(Get $get, mixed $livewire): ?int
    {
        $delFormulario = $get('cliente_id');

        if ($delFormulario) {
            return (int) $delFormulario;
        }

        return method_exists($livewire, 'getOwnerRecord')
            ? $livewire->getOwnerRecord()?->getKey()
            : null;
    }

    /**
     * @return array<int, string>
     */
    private static function prediosDelTitular(?int $clienteId): array
    {
        if ($clienteId === null) {
            return [];
        }

        return Cliente::find($clienteId)
            ?->predios()
            ->get()
            ->mapWithKeys(fn (Predio $predio): array => [
                $predio->id => $predio->direccion_completa ?: "Predio #{$predio->getKey()}",
            ])
            ->all() ?? [];
    }
}
