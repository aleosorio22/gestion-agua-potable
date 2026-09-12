<?php

use App\Enums\FormatoPapel;
use App\Http\Controllers\InstaladorController;
use App\Models\Configuracion;
use App\Models\User;
use App\Services\Instalador;
use Database\Seeders\ShieldSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * La puesta en marcha en un servidor nuevo.
 *
 * Lo que más importa demostrar son los dos candados: que el instalador no se
 * pueda volver a abrir una vez que la oficina está operando, y que no pise una
 * base que ya tiene datos.
 */
beforeEach(function () {
    $this->instalador = app(Instalador::class);
});

/** Pone el sistema en el estado de un servidor recién montado. */
function sinInstalar(): void
{
    config(['app.installed' => false]);
}

it('manda al instalador cualquier url mientras el sistema no este instalado', function () {
    sinInstalar();

    $this->get('/admin')->assertRedirect(route('instalador.bienvenida'));
});

it('deja pasar las rutas del propio instalador', function () {
    sinInstalar();

    $this->get(route('instalador.bienvenida'))->assertOk();
    $this->get(route('instalador.requisitos'))->assertOk();
});

it('cierra el instalador una vez que el sistema esta en marcha', function () {
    // Dejarlo accesible permitiría reescribir el `.env` y el administrador de
    // una oficina que ya está operando.
    $this->get(route('instalador.bienvenida'))->assertRedirect('/admin');
    $this->get(route('instalador.base-de-datos'))->assertRedirect('/admin');
});

it('revisa la version de php, las extensiones y los permisos', function () {
    $revision = $this->instalador->requisitos();

    $grupos = collect($revision['requisitos'])->pluck('grupo')->unique();

    expect($grupos)->toContain('Versión de PHP', 'Extensiones de PHP', 'Permisos de escritura')
        // El entorno que corre estas pruebas cumple por definición: si no,
        // no habrían llegado a ejecutarse.
        ->and($revision['cumple'])->toBeTrue();
});

it('explica para que sirve cada extension que falta', function () {
    $revision = $this->instalador->requisitos();

    $extension = collect($revision['requisitos'])
        ->firstWhere('etiqueta', 'gd');

    // Un mensaje que solo diga «falta gd» obliga a buscar en Google qué se
    // rompe sin ella.
    expect($extension['detalle'])->toContain('logotipo');
});

it('avisa cuando no se puede conectar a la base', function () {
    $prueba = $this->instalador->probarBaseDeDatos([
        'host' => '127.0.0.1',
        'puerto' => '3306',
        'base' => 'una-base-que-no-existe-'.uniqid(),
        'usuario' => 'root',
        'contrasena' => 'contrasena-incorrecta',
    ]);

    expect($prueba['conecta'])->toBeFalse()
        // Sin filtrar a la pantalla el mensaje crudo de MySQL, que menciona
        // usuarios y rutas del servidor.
        ->and($prueba['mensaje'])->not->toContain('SQLSTATE');
});

it('no deja avanzar a los pasos siguientes sin configurar la base', function () {
    sinInstalar();

    $this->get(route('instalador.oficina'))->assertRedirect(route('instalador.base-de-datos'));
    $this->get(route('instalador.administrador'))->assertRedirect(route('instalador.oficina'));
});

it('exige los datos de la oficina y una contraseña segura', function () {
    sinInstalar();

    $this->post(route('instalador.oficina.guardar'), [])
        ->assertSessionHasErrors(['nombre', 'formato']);

    $this->post(route('instalador.instalar'), [
        'nombre' => 'Quien Sea',
        'email' => 'no-es-un-correo',
        'password' => 'corta',
        'password_confirmation' => 'otra',
    ])->assertSessionHasErrors(['email', 'password']);
});

it('guarda los datos de la oficina entre pasos sin escribir nada todavia', function () {
    sinInstalar();
    Storage::fake('local');

    $this->withSession(['instalador.base' => ['host' => '127.0.0.1']])
        ->post(route('instalador.oficina.guardar'), [
            'nombre' => 'Comité de Agua El Porvenir',
            'municipio' => 'El Progreso',
            'formato' => FormatoPapel::Carta->value,
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])
        ->assertRedirect(route('instalador.administrador'));

    $guardado = session('instalador.oficina');

    expect($guardado['nombre'])->toBe('Comité de Agua El Porvenir')
        ->and(Storage::disk('local')->exists($guardado['logo']))->toBeTrue()
        // Nada llegó a `configuracion`: eso pasa recién en el último paso.
        ->and(Configuracion::where('clave', 'entidad.nombre')->exists())->toBeFalse();
});

it('descarta el logotipo si la instalacion se cancela a medias', function () {
    sinInstalar();
    Storage::fake('local');
    Storage::disk('local')->put('configuracion/logo.png', 'contenido');

    $this->withSession(['instalador.oficina' => ['logo' => 'configuracion/logo.png']])
        ->get(route('instalador.cancelar'))
        ->assertRedirect(route('instalador.bienvenida'));

    expect(Storage::disk('local')->exists('configuracion/logo.png'))->toBeFalse()
        ->and(session('instalador'))->toBeNull();
});

it('escribe el env conservando lo que ya estaba configurado', function () {
    $original = file_get_contents(base_path('.env'));

    try {
        $this->instalador->guardarEnEnv([
            'APP_NAME' => 'Comité de Agua El Porvenir',
            'UNA_CLAVE_QUE_NO_EXISTIA' => 'valor',
        ]);

        $contenido = file_get_contents(base_path('.env'));

        expect($contenido)
            // Entrecomillado: sin comillas, el parser corta en el primer espacio.
            ->toContain('APP_NAME="Comité de Agua El Porvenir"')
            ->toContain('UNA_CLAVE_QUE_NO_EXISTIA=valor')
            // Reescribe línea por línea, no genera el archivo de cero.
            ->toContain('DB_CONNECTION=');
    } finally {
        file_put_contents(base_path('.env'), $original);
    }
});

it('crea el administrador y deja la oficina configurada', function () {
    $this->seed(ShieldSeeder::class);

    // El paso final, sin pasar por el `.env` ni las migraciones: eso ya lo
    // cubre la suite entera, que corre sobre una base migrada.
    $controlador = new ReflectionClass(InstaladorController::class);
    $instancia = $controlador->newInstance($this->instalador);

    $crear = $controlador->getMethod('crearAdministrador');
    $crear->invoke($instancia, [
        'nombre' => 'Ana López',
        'email' => 'ana@oficina-agua.test',
        'password' => 'clave-segura-1',
    ]);

    $guardar = $controlador->getMethod('guardarDatosDeLaOficina');
    $guardar->invoke($instancia, [
        'nombre' => 'Comité de Agua El Porvenir',
        'municipio' => 'El Progreso',
        'formato' => FormatoPapel::Oficio->value,
        'logo' => 'configuracion/logo.png',
    ]);

    $admin = User::where('email', 'ana@oficina-agua.test')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->hasRole(config('filament-shield.super_admin.name')))->toBeTrue()
        ->and($admin->activo)->toBeTrue()
        ->and(Configuracion::obtener('entidad.nombre'))->toBe('Comité de Agua El Porvenir')
        ->and(Configuracion::obtener('impresion.formato'))->toBe(FormatoPapel::Oficio->value)
        // Cargar un logo enciende su interruptor: pedirlo y que no salga sería
        // un paso extra invisible.
        ->and(Configuracion::obtener('impresion.mostrar_logo'))->toBe('1');
});

it('no enciende el logotipo si la oficina no cargo ninguno', function () {
    $controlador = new ReflectionClass(InstaladorController::class);
    $instancia = $controlador->newInstance($this->instalador);

    $controlador->getMethod('guardarDatosDeLaOficina')->invoke($instancia, [
        'nombre' => 'Sin logotipo',
        'formato' => FormatoPapel::Termica80->value,
    ]);

    expect(Configuracion::obtener('impresion.mostrar_logo'))->toBeNull();
});
