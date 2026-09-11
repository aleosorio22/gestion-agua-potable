<?php

use App\Filament\Admin\Resources\Clientes\ClienteResource;
use App\Filament\Admin\Resources\Clientes\RelationManagers\DocumentosRelationManager;
use App\Filament\Admin\Resources\Documentos\DocumentoResource;
use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Documento;
use App\Models\Predio;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Services\MetadatosDeArchivo;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * El expediente: la foto o el PDF que respalda a la persona o a su propiedad.
 *
 * Lo que interesa demostrar es que un documento de propiedad no pueda quedar
 * colgando sin decir cuál, ni apuntar a la casa de otro, y que el archivo no
 * quede accesible a quien no debe verlo.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');

    Storage::fake('local');

    $this->seed(ShieldSeeder::class);

    $this->actingAs(
        User::factory()->create()->assignRole(
            Role::findByName(config('filament-shield.super_admin.name'), 'web')
        )
    );

    $this->dpi = TipoDocumento::factory()->create(['nombre' => 'DPI', 'respalda_predio' => false]);
    $this->escritura = TipoDocumento::factory()->create(['nombre' => 'Escritura', 'respalda_predio' => true]);
});

/** Un cliente con servicio en una propiedad concreta. */
function titularConPropiedad(): Cliente
{
    $contador = Contador::factory()->create();

    return $contador->cliente;
}

function archivoDePrueba(string $nombre = 'escritura.pdf'): UploadedFile
{
    return UploadedFile::fake()->create($nombre, 120, 'application/pdf');
}

it('pinta el listado y el formulario de documentos', function () {
    Documento::factory()->count(2)->create();

    Livewire::test(DocumentoResource::getPages()['index']->getPage())->assertSuccessful();
    Livewire::test(DocumentoResource::getPages()['create']->getPage())->assertSuccessful();
});

it('carga un documento de la persona sin pedir propiedad', function () {
    $cliente = Cliente::factory()->create();

    Livewire::test(DocumentoResource::getPages()['create']->getPage())
        ->fillForm([
            'cliente_id' => $cliente->id,
            'tipo_documento_id' => $this->dpi->id,
            'ruta' => archivoDePrueba('dpi.pdf'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $documento = Documento::first();

    expect($documento->predio_id)->toBeNull()
        ->and($documento->cliente_id)->toBe($cliente->id);
});

it('exige la propiedad cuando el tipo respalda un predio', function () {
    $cliente = titularConPropiedad();

    Livewire::test(DocumentoResource::getPages()['create']->getPage())
        ->fillForm([
            'cliente_id' => $cliente->id,
            'tipo_documento_id' => $this->escritura->id,
            'ruta' => archivoDePrueba(),
        ])
        ->call('create')
        ->assertHasFormErrors(['predio_id']);

    expect(Documento::count())->toBe(0);
});

it('solo ofrece las propiedades del propio titular', function () {
    $cliente = titularConPropiedad();
    $suyo = $cliente->predios()->first();
    $ajeno = Predio::factory()->create();

    $opciones = Livewire::test(DocumentoResource::getPages()['create']->getPage())
        ->fillForm([
            'cliente_id' => $cliente->id,
            'tipo_documento_id' => $this->escritura->id,
        ])
        ->instance()
        ->form
        ->getComponent('predio_id')
        ->getOptions();

    expect($opciones)->toHaveKey($suyo->id)
        ->and($opciones)->not->toHaveKey($ajeno->id);
});

it('vincula la escritura con la propiedad que respalda', function () {
    $cliente = titularConPropiedad();
    $predio = $cliente->predios()->first();

    Livewire::test(DocumentoResource::getPages()['create']->getPage())
        ->fillForm([
            'cliente_id' => $cliente->id,
            'tipo_documento_id' => $this->escritura->id,
            'predio_id' => $predio->id,
            'ruta' => archivoDePrueba(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Documento::first()->predio_id)->toBe($predio->id);
});

it('calcula el hash, el tamaño y el mime del archivo cargado', function () {
    $cliente = Cliente::factory()->create();

    Livewire::test(DocumentoResource::getPages()['create']->getPage())
        ->fillForm([
            'cliente_id' => $cliente->id,
            'tipo_documento_id' => $this->dpi->id,
            'ruta' => archivoDePrueba('dpi.pdf'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $documento = Documento::first();

    // Contra el archivo que quedó en disco y no contra lo que dijo el
    // formulario: `UploadedFile::fake()` reporta un tamaño que no se
    // corresponde con su contenido, así que comparar con la constante que
    // pasamos arriba no probaría que el observer leyó nada.
    expect($documento->disco)->toBe('local')
        ->and($documento->mime)->toBe('application/pdf')
        ->and($documento->subido_por)->toBe(auth()->id())
        ->and(Storage::disk('local')->exists($documento->ruta))->toBeTrue()
        ->and($documento->tamano_bytes)->toBe(Storage::disk('local')->size($documento->ruta))
        ->and($documento->hash_sha256)->toBe(
            hash('sha256', Storage::disk('local')->get($documento->ruta))
        );
});

it('guarda el archivo fuera del disco publico', function () {
    $cliente = Cliente::factory()->create();

    Livewire::test(DocumentoResource::getPages()['create']->getPage())
        ->fillForm([
            'cliente_id' => $cliente->id,
            'tipo_documento_id' => $this->dpi->id,
            'ruta' => archivoDePrueba('dpi.pdf'),
        ])
        ->call('create');

    // Un DPI escaneado no puede quedar descargable por URL a quien la adivine.
    expect(Documento::first()->disco)->not->toBe('public');
});

it('detecta que el archivo fue reemplazado despues de cargarlo', function () {
    $metadatos = app(MetadatosDeArchivo::class);

    Storage::disk('local')->put('documentos/escritura.pdf', 'contenido original');
    $hash = $metadatos->hash('local', 'documentos/escritura.pdf');

    expect($metadatos->coincide('local', 'documentos/escritura.pdf', $hash))->toBeTrue();

    Storage::disk('local')->put('documentos/escritura.pdf', 'contenido sustituido');

    expect($metadatos->coincide('local', 'documentos/escritura.pdf', $hash))->toBeFalse();
});

it('borra el archivo del disco al eliminar el documento', function () {
    Storage::disk('local')->put('documentos/temporal.pdf', 'contenido');

    $documento = Documento::factory()->create([
        'disco' => 'local',
        'ruta' => 'documentos/temporal.pdf',
    ]);

    Livewire::test(DocumentoResource::getPages()['index']->getPage())
        ->callTableAction('delete', $documento);

    expect(Storage::disk('local')->exists('documentos/temporal.pdf'))->toBeFalse()
        ->and(Documento::count())->toBe(0);
});

it('entrega el archivo a quien tiene permiso', function () {
    Storage::disk('local')->put('documentos/dpi.pdf', 'contenido');

    $documento = Documento::factory()->create([
        'disco' => 'local',
        'ruta' => 'documentos/dpi.pdf',
        'nombre_original' => 'dpi.pdf',
    ]);

    $this->get(route('documentos.descargar', $documento))->assertOk();
});

it('niega el archivo a quien no puede ver documentos', function () {
    Storage::disk('local')->put('documentos/dpi.pdf', 'contenido');

    $documento = Documento::factory()->create([
        'disco' => 'local',
        'ruta' => 'documentos/dpi.pdf',
    ]);

    $this->actingAs(User::factory()->create());

    $this->get(route('documentos.descargar', $documento))->assertForbidden();
});

it('avisa en vez de entregar un archivo que ya no esta en disco', function () {
    $documento = Documento::factory()->create([
        'disco' => 'local',
        'ruta' => 'documentos/perdido.pdf',
    ]);

    $this->get(route('documentos.descargar', $documento))->assertNotFound();
});

it('carga el expediente desde la ficha del cliente sin preguntar el titular', function () {
    $cliente = Cliente::factory()->create();

    Livewire::test(DocumentosRelationManager::class, [
        'ownerRecord' => $cliente,
        'pageClass' => ClienteResource::getPages()['edit']->getPage(),
    ])
        ->assertSuccessful()
        ->callTableAction('create', data: [
            'tipo_documento_id' => $this->dpi->id,
            'ruta' => archivoDePrueba('dpi.pdf'),
        ])
        ->assertHasNoTableActionErrors();

    expect(Documento::first()->cliente_id)->toBe($cliente->id);
});

it('muestra en la ficha solo los documentos de ese cliente', function () {
    $cliente = Cliente::factory()->create();
    $suyo = Documento::factory()->create(['cliente_id' => $cliente->id]);
    $ajeno = Documento::factory()->create();

    Livewire::test(DocumentosRelationManager::class, [
        'ownerRecord' => $cliente,
        'pageClass' => ClienteResource::getPages()['edit']->getPage(),
    ])
        ->assertCanSeeTableRecords([$suyo])
        ->assertCanNotSeeTableRecords([$ajeno]);
});
