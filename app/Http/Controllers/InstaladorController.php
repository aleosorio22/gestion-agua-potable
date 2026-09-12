<?php

namespace App\Http\Controllers;

use App\Enums\FormatoPapel;
use App\Models\Configuracion;
use App\Models\User;
use App\Services\Instalador;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Throwable;

/**
 * Las cinco pantallas que ponen el sistema en marcha.
 *
 * Cada paso guarda lo suyo en sesión y recién el último escribe: si algo falla
 * a la mitad, no queda un `.env` a medio armar apuntando a una base que no
 * migró.
 */
class InstaladorController extends Controller
{
    public function __construct(private readonly Instalador $instalador) {}

    public function bienvenida(): View
    {
        return view('instalador.bienvenida');
    }

    public function requisitos(): View
    {
        return view('instalador.requisitos', $this->instalador->requisitos());
    }

    public function baseDeDatos(): View
    {
        return view('instalador.base-de-datos', [
            'datos' => $this->instalador->recordar('base') ?: [
                'host' => '127.0.0.1',
                'puerto' => '3306',
            ],
        ]);
    }

    public function probarBaseDeDatos(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'puerto' => ['required', 'integer', 'min:1', 'max:65535'],
            'base' => ['required', 'string', 'max:64'],
            'usuario' => ['required', 'string', 'max:64'],
            'contrasena' => ['nullable', 'string', 'max:255'],
        ], [], [
            'host' => 'el servidor',
            'puerto' => 'el puerto',
            'base' => 'el nombre de la base',
            'usuario' => 'el usuario',
            'contrasena' => 'la contraseña',
        ]);

        $prueba = $this->instalador->probarBaseDeDatos($datos);

        if (! $prueba['conecta']) {
            return back()->withInput()->withErrors(['host' => $prueba['mensaje']]);
        }

        // Con tablas dentro, seguir significaría migrar sobre datos ajenos. Se
        // avisa y se detiene: vaciar la base de una oficina en marcha no es
        // algo que deba ofrecer un botón.
        if ($prueba['tablas'] > 0) {
            return back()->withInput()->withErrors([
                'base' => $prueba['mensaje'].' Use una base vacía o elimínela y vuelva a crearla antes de continuar.',
            ]);
        }

        $this->instalador->recordar('base', $datos);

        return redirect()->route('instalador.oficina');
    }

    public function oficina(): RedirectResponse|View
    {
        if (! $this->instalador->completo('base')) {
            return redirect()->route('instalador.base-de-datos');
        }

        return view('instalador.oficina', [
            'datos' => $this->instalador->recordar('oficina'),
            'formatos' => FormatoPapel::opciones(),
        ]);
    }

    public function guardarOficina(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'nit' => ['nullable', 'string', 'max:20'],
            'municipio' => ['nullable', 'string', 'max:100'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'formato' => ['required', 'string', 'in:'.implode(',', array_keys(FormatoPapel::opciones()))],
            'logo' => ['nullable', 'image', 'max:2048'],
        ], [], [
            'nombre' => 'el nombre de la oficina',
            'formato' => 'el formato de papel',
            'logo' => 'el logotipo',
        ]);

        // El archivo no entra en sesión: se guarda ya y solo viaja su ruta.
        if ($request->hasFile('logo')) {
            $datos['logo'] = $request->file('logo')->store('configuracion', 'local');
        }

        $this->instalador->recordar('oficina', $datos);

        return redirect()->route('instalador.administrador');
    }

    public function administrador(): RedirectResponse|View
    {
        if (! $this->instalador->completo('oficina')) {
            return redirect()->route('instalador.oficina');
        }

        return view('instalador.administrador', [
            'datos' => $this->instalador->recordar('administrador'),
        ]);
    }

    public function instalar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'datos_de_ejemplo' => ['nullable', 'boolean'],
        ], [], [
            'nombre' => 'el nombre',
            'email' => 'el correo electrónico',
            'password' => 'la contraseña',
        ]);

        if (! $this->instalador->completo('base') || ! $this->instalador->completo('oficina')) {
            return redirect()->route('instalador.base-de-datos');
        }

        try {
            $this->ejecutarInstalacion($datos);
        } catch (Throwable $error) {
            return back()->withInput()->withErrors([
                'email' => $this->instalador->registrarFalla($error),
            ]);
        }

        $this->instalador->olvidarTodo();

        return redirect()->route('instalador.listo')->with('correo', $datos['email']);
    }

    /**
     * El único paso que escribe.
     *
     * El orden importa: el `.env` primero porque las migraciones necesitan la
     * conexión, y el flag de instalado al final porque mientras no esté, una
     * instalación fallida se puede reintentar desde cero.
     *
     * @param  array<string, mixed>  $administrador
     */
    private function ejecutarInstalacion(array $administrador): void
    {
        $base = $this->instalador->recordar('base');
        $oficina = $this->instalador->recordar('oficina');

        $this->instalador->guardarEnEnv([
            'APP_NAME' => $oficina['nombre'],
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $base['host'],
            'DB_PORT' => (string) $base['puerto'],
            'DB_DATABASE' => $base['base'],
            'DB_USERNAME' => $base['usuario'],
            'DB_PASSWORD' => $base['contrasena'] ?? '',
        ]);

        $this->instalador->usarBaseDeDatos($base);

        if ($this->instalador->necesitaClaveDeAplicacion()) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--class' => 'ShieldSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'ConfiguracionSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'CatalogosSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'SerieDocumentoSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'PeriodoSeeder', '--force' => true]);

        $this->crearAdministrador($administrador);
        $this->guardarDatosDeLaOficina($oficina);

        // Desmarcados por defecto: una instalación real no debe recibir cuentas
        // de prueba con contraseña conocida.
        if ($administrador['datos_de_ejemplo'] ?? false) {
            Artisan::call('db:seed', ['--class' => 'ClienteDemoSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'LectorDemoSeeder', '--force' => true]);
        }

        $this->instalador->marcarInstalado();
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function crearAdministrador(array $datos): void
    {
        $usuario = User::updateOrCreate(
            ['email' => $datos['email']],
            [
                'name' => $datos['nombre'],
                'password' => Hash::make($datos['password']),
                'activo' => true,
            ],
        );

        $usuario->assignRole(config('filament-shield.super_admin.name', 'super_admin'));
    }

    /**
     * @param  array<string, mixed>  $oficina
     */
    private function guardarDatosDeLaOficina(array $oficina): void
    {
        $ajustes = [
            'entidad.nombre' => $oficina['nombre'],
            'entidad.nit' => $oficina['nit'] ?? null,
            'entidad.telefono' => $oficina['telefono'] ?? null,
            'entidad.direccion' => $oficina['direccion'] ?? null,
            'ubicacion.municipio' => $oficina['municipio'] ?? null,
            'ubicacion.departamento' => $oficina['departamento'] ?? null,
            'impresion.formato' => $oficina['formato'],
        ];

        if (filled($oficina['logo'] ?? null)) {
            $ajustes['entidad.logo'] = $oficina['logo'];
            $ajustes['impresion.mostrar_logo'] = '1';
        }

        foreach ($ajustes as $clave => $valor) {
            Configuracion::guardar($clave, $valor);
        }
    }

    public function listo(): View
    {
        return view('instalador.listo', [
            'correo' => session('correo'),
        ]);
    }

    /**
     * Vuelve a mirar el servidor sin recargar a mano, para que el técnico
     * arregle un permiso y compruebe en el acto.
     */
    public function revisarRequisitos(): RedirectResponse
    {
        return redirect()->route('instalador.requisitos');
    }

    /**
     * Descarta un logotipo que quedó subido si la instalación no se terminó.
     */
    public function cancelar(): RedirectResponse
    {
        $logo = $this->instalador->recordar('oficina')['logo'] ?? null;

        if (filled($logo)) {
            Storage::disk('local')->delete($logo);
        }

        $this->instalador->olvidarTodo();

        return redirect()->route('instalador.bienvenida');
    }
}
