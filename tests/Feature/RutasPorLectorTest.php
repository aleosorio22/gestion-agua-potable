<?php

use App\Filament\Admin\Resources\Usuarios\UsuarioResource;
use App\Filament\Lector\Pages\RutaDeLectura;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Periodo;
use App\Models\Predio;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * El reparto del padrón entre lectores.
 *
 * Sin esto todos veían todos los contadores, y la única barrera contra el
 * recorrido duplicado era el unique de `lecturas`: avisaba recién cuando el
 * segundo lector ya había caminado hasta la casa.
 */
beforeEach(function () {
    Filament::setCurrentPanel('lector');

    $this->seed(ShieldSeeder::class);
    $this->seed(RoleSeeder::class);

    Periodo::factory()->create();

    $this->norte = Sector::factory()->create(['nombre' => 'Norte', 'orden' => 1]);
    $this->sur = Sector::factory()->create(['nombre' => 'Sur', 'orden' => 2]);
});

function lectorDe(Sector ...$sectores): User
{
    $lector = User::factory()->create()->assignRole(Role::findByName('Lector', 'web'));

    $lector->sectores()->sync(collect($sectores)->pluck('id'));

    return $lector;
}

function contadorEn(?Sector $sector): Contador
{
    return Contador::factory()->create([
        'predio_id' => Predio::factory()->create(['sector_id' => $sector?->id])->id,
    ]);
}

it('le muestra al lector solo los contadores de sus sectores', function () {
    $suyo = contadorEn($this->norte);
    $ajeno = contadorEn($this->sur);

    $this->actingAs(lectorDe($this->norte));

    Livewire::test(RutaDeLectura::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$suyo])
        ->assertCanNotSeeTableRecords([$ajeno]);
});

it('deja ver todo el padron al lector sin sectores asignados', function () {
    $norte = contadorEn($this->norte);
    $sur = contadorEn($this->sur);

    // La oficina de un solo lector no tiene nada que repartir.
    $this->actingAs(lectorDe());

    Livewire::test(RutaDeLectura::class)
        ->assertCanSeeTableRecords([$norte, $sur]);
});

it('pone los predios sin sector en la ruta de cualquier lector', function () {
    $sinSector = contadorEn(null);
    $suyo = contadorEn($this->norte);

    // Dejarlos fuera los condenaría a no leerse nunca.
    $this->actingAs(lectorDe($this->norte));

    Livewire::test(RutaDeLectura::class)
        ->assertCanSeeTableRecords([$suyo, $sinSector]);
});

it('reparte el padron entre dos lectores sin que se pisen', function () {
    $delNorte = contadorEn($this->norte);
    $delSur = contadorEn($this->sur);

    $this->actingAs(lectorDe($this->norte));
    Livewire::test(RutaDeLectura::class)
        ->assertCanSeeTableRecords([$delNorte])
        ->assertCanNotSeeTableRecords([$delSur]);

    $this->actingAs(lectorDe($this->sur));
    Livewire::test(RutaDeLectura::class)
        ->assertCanSeeTableRecords([$delSur])
        ->assertCanNotSeeTableRecords([$delNorte]);
});

it('atiende varios sectores asignados al mismo lector', function () {
    $norte = contadorEn($this->norte);
    $sur = contadorEn($this->sur);

    $this->actingAs(lectorDe($this->norte, $this->sur));

    Livewire::test(RutaDeLectura::class)
        ->assertCanSeeTableRecords([$norte, $sur]);
});

it('mide el avance contra su recorrido y no contra el padron entero', function () {
    contadorEn($this->norte);
    $otroSuyo = contadorEn($this->norte);
    contadorEn($this->sur);
    contadorEn($this->sur);

    $lector = lectorDe($this->norte);
    $this->actingAs($lector);

    $periodo = Periodo::first();
    Lectura::factory()->create(['contador_id' => $otroSuyo->id, 'periodo_id' => $periodo->id]);

    // Dos suyos, uno leído. Contra el padrón entero diría «1 de 4» y le haría
    // creer que va por la cuarta parte para siempre.
    expect(Livewire::test(RutaDeLectura::class)->instance()->avance)
        ->toBe(['leidos' => 1, 'total' => 2]);
});

it('deja fuera de la ruta un contador inactivo aunque sea de su sector', function () {
    $activo = contadorEn($this->norte);
    $inactivo = contadorEn($this->norte);
    $inactivo->update(['estado' => 'inactivo']);

    $this->actingAs(lectorDe($this->norte));

    Livewire::test(RutaDeLectura::class)
        ->assertCanSeeTableRecords([$activo])
        ->assertCanNotSeeTableRecords([$inactivo]);
});

it('asigna los sectores del lector desde la pantalla de usuarios', function () {
    Filament::setCurrentPanel('admin');

    $this->actingAs(
        User::factory()->create()->assignRole(
            Role::findByName(config('filament-shield.super_admin.name'), 'web')
        )
    );

    $lector = Role::findByName('Lector', 'web');

    Livewire::test(UsuarioResource::getPages()['create']->getPage())
        ->fillForm([
            'name' => 'Pedro Cúmez',
            'email' => 'pedro@oficina-agua.test',
            'password' => 'clave-segura-1',
            'password_confirmation' => 'clave-segura-1',
            'roles' => [$lector->id],
            'sectores' => [$this->norte->id, $this->sur->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::where('email', 'pedro@oficina-agua.test')->first()->sectores)
        ->toHaveCount(2);
});

it('libera los sectores al eliminar la cuenta del lector', function () {
    $lector = lectorDe($this->norte);

    expect(DB::table('lector_sectores')->count())->toBe(1);

    $lector->delete();

    // La asignación no tiene vida propia: sin lector no significa nada.
    expect(DB::table('lector_sectores')->count())->toBe(0);
});
