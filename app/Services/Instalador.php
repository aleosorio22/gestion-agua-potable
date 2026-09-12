<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use Throwable;

/**
 * Puesta en marcha del sistema en un servidor nuevo.
 *
 * Todo lo que hace acá se podría hacer a mano —editar el `.env`, correr
 * `migrate --seed`, entrar a Configuración— pero quien instala esto en una
 * oficina municipal no necesariamente sabe qué es una terminal. El instalador
 * convierte eso en cinco pantallas.
 *
 * No toca la base más allá de migrar y sembrar: si encuentra tablas, avisa y
 * se detiene. Un sistema que guarda boletas y pagos de vecinos no debe tener a
 * mano un botón que vacíe la base.
 */
class Instalador
{
    /**
     * Extensiones de PHP sin las que el sistema no arranca, con el motivo por
     * el que hacen falta: un mensaje que solo dice «falta gd» obliga a buscar
     * en Google qué se rompe sin ella.
     */
    private const EXTENSIONES = [
        'pdo' => 'Conexión a la base de datos',
        'pdo_mysql' => 'Conexión a MySQL o MariaDB',
        'mbstring' => 'Manejo de tildes y ñ',
        'openssl' => 'Cifrado de contraseñas y sesiones',
        'tokenizer' => 'Requerida por Laravel',
        'json' => 'Requerida por Laravel',
        'ctype' => 'Requerida por Laravel',
        'fileinfo' => 'Detecta el tipo de los documentos que se suben',
        'gd' => 'Procesa el logotipo de la oficina',
        'curl' => 'Requerida por Laravel',
        'intl' => 'Ordena nombres y da formato a fechas y cantidades',
        'zip' => 'Arma los reportes en Excel',
        'dom' => 'Arma las boletas y los reportes en PDF',
        'xml' => 'Arma los reportes en Excel',
        'iconv' => 'Convierte los textos al exportar',
    ];

    /**
     * La misma que exige el `composer.json`: si el servidor trae una anterior,
     * las dependencias ya instaladas no corren y el error aparece después,
     * suelto y sin explicación.
     */
    private const PHP_MINIMO = '8.3.0';

    public function estaInstalado(): bool
    {
        return (bool) config('app.installed', false);
    }

    /**
     * Qué le falta al servidor para poder correr el sistema.
     *
     * @return array{requisitos: array<int, array{grupo: string, etiqueta: string, cumple: bool, detalle: string}>, cumple: bool}
     */
    public function requisitos(): array
    {
        $requisitos = [];

        $phpOk = version_compare(PHP_VERSION, self::PHP_MINIMO, '>=');
        $requisitos[] = [
            'grupo' => 'Versión de PHP',
            'etiqueta' => 'PHP '.PHP_VERSION,
            'cumple' => $phpOk,
            'detalle' => $phpOk
                ? 'Versión compatible'
                : 'Se necesita PHP '.self::PHP_MINIMO.' o superior',
        ];

        foreach (self::EXTENSIONES as $extension => $paraQue) {
            $cargada = extension_loaded($extension);
            $requisitos[] = [
                'grupo' => 'Extensiones de PHP',
                'etiqueta' => $extension,
                'cumple' => $cargada,
                'detalle' => $cargada ? $paraQue : "Falta instalarla. Se usa para: {$paraQue}",
            ];
        }

        foreach ($this->rutasQueSeEscriben() as $ruta => $paraQue) {
            $escribible = $this->sePuedeEscribirEn($ruta);
            $requisitos[] = [
                'grupo' => 'Permisos de escritura',
                'etiqueta' => str_replace(base_path().'/', '', $ruta),
                'cumple' => $escribible,
                'detalle' => $escribible ? $paraQue : 'El servidor no puede escribir aquí',
            ];
        }

        return [
            'requisitos' => $requisitos,
            'cumple' => collect($requisitos)->every(fn (array $r): bool => $r['cumple']),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function rutasQueSeEscriben(): array
    {
        return [
            base_path('.env') => 'Guarda la configuración del servidor',
            storage_path('app') => 'Guarda los documentos escaneados',
            storage_path('framework') => 'Archivos temporales del sistema',
            storage_path('logs') => 'Registro de errores',
            base_path('bootstrap/cache') => 'Caché de arranque',
        ];
    }

    /**
     * Un archivo que todavía no existe se puede crear si su carpeta admite
     * escritura: es el caso del `.env` en una instalación desde cero.
     */
    private function sePuedeEscribirEn(string $ruta): bool
    {
        if (file_exists($ruta)) {
            return is_writable($ruta);
        }

        $carpeta = dirname($ruta);

        return is_dir($carpeta) && is_writable($carpeta);
    }

    /**
     * Prueba la conexión sin tocar el `.env`, y avisa si la base ya tiene
     * tablas en vez de pisarlas.
     *
     * @param  array<string, mixed>  $datos
     * @return array{conecta: bool, mensaje: string, tablas: int}
     */
    public function probarBaseDeDatos(array $datos): array
    {
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s', $datos['host'], $datos['puerto'], $datos['base']),
                $datos['usuario'],
                $datos['contrasena'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5],
            );

            $tablas = (int) $pdo->query('SHOW TABLES')->rowCount();

            return [
                'conecta' => true,
                'tablas' => $tablas,
                'mensaje' => $tablas > 0
                    ? "La conexión funciona, pero la base «{$datos['base']}» ya tiene {$tablas} tablas."
                    : 'La conexión funciona y la base está vacía.',
            ];
        } catch (PDOException $error) {
            return [
                'conecta' => false,
                'tablas' => 0,
                'mensaje' => $this->explicar($error),
            ];
        }
    }

    /**
     * Traduce el error de PDO a algo accionable.
     *
     * El mensaje crudo de MySQL menciona usuarios y rutas del servidor; además
     * de ser ilegible para quien instala, no conviene mostrarlo en pantalla.
     */
    private function explicar(PDOException $error): string
    {
        return match ((int) $error->getCode()) {
            1045 => 'El usuario o la contraseña no son correctos.',
            1049 => 'Esa base de datos no existe. Créela primero o revise el nombre.',
            2002 => 'No se pudo contactar al servidor de base de datos. Revise el host y el puerto.',
            default => 'No se pudo conectar con esos datos. Revise que el servidor de base de datos esté encendido.',
        };
    }

    /**
     * Escribe en el `.env` lo que el instalador fue recogiendo.
     *
     * Reescribe línea por línea en vez de generar el archivo de cero para no
     * perder lo que el servidor ya tuviera configurado —correo, colas, otra
     * clave de aplicación—.
     *
     * @param  array<string, string>  $valores
     */
    public function guardarEnEnv(array $valores): void
    {
        $ruta = base_path('.env');
        $contenido = is_file($ruta) ? (string) file_get_contents($ruta) : '';

        foreach ($valores as $clave => $valor) {
            $contenido = $this->reemplazarLinea($contenido, $clave, $valor);
        }

        file_put_contents($ruta, $contenido);
    }

    private function reemplazarLinea(string $contenido, string $clave, string $valor): string
    {
        // Entrecomillado cuando trae espacios o caracteres que el parser del
        // .env interpreta: «Comité de Agua El Porvenir» sin comillas se corta
        // en el primer espacio.
        $escapado = preg_match('/^[a-zA-Z0-9_\/.:-]*$/', $valor) === 1
            ? $valor
            : '"'.str_replace('"', '\"', $valor).'"';

        $linea = "{$clave}={$escapado}";
        $patron = '/^\s*'.preg_quote($clave, '/').'\s*=.*$/m';

        if (preg_match($patron, $contenido) === 1) {
            return (string) preg_replace($patron, $linea, $contenido);
        }

        return rtrim($contenido, "\n")."\n".$linea."\n";
    }

    /**
     * Deja la conexión apuntando a la base que se acaba de configurar, sin
     * esperar a reiniciar el proceso.
     *
     * @param  array<string, mixed>  $datos
     */
    public function usarBaseDeDatos(array $datos): void
    {
        config([
            'database.connections.mysql.host' => $datos['host'],
            'database.connections.mysql.port' => $datos['puerto'],
            'database.connections.mysql.database' => $datos['base'],
            'database.connections.mysql.username' => $datos['usuario'],
            'database.connections.mysql.password' => $datos['contrasena'] ?? '',
            'database.default' => 'mysql',
        ]);

        DB::purge('mysql');
    }

    /**
     * Marca la instalación como terminada.
     *
     * Va al final de todo: mientras el flag no esté, el middleware sigue
     * mandando al instalador, así que una instalación que falló a mitad se
     * puede reintentar desde el principio.
     */
    public function marcarInstalado(): void
    {
        $this->guardarEnEnv(['APP_INSTALLED' => 'true']);

        // La configuración ya está cargada en memoria y, si el servidor corrió
        // `config:cache`, ni siquiera relee el `.env`: sin esto el sistema
        // seguiría creyéndose sin instalar después de instalarse.
        config(['app.installed' => true]);
        Artisan::call('config:clear');
    }

    /**
     * Si la aplicación todavía no tiene clave propia.
     *
     * Sin esto, dos instalaciones distintas compartirían la clave del
     * repositorio y podrían descifrarse las sesiones entre sí.
     */
    public function necesitaClaveDeAplicacion(): bool
    {
        return blank(config('app.key'));
    }

    /**
     * Los datos que el instalador va guardando entre pasos.
     *
     * Viven en sesión y no en archivo: el instalador se corre de una sentada, y
     * un archivo temporal con la contraseña de la base en texto plano es
     * justamente lo que no se quiere dejar olvidado en el servidor.
     *
     * @return array<string, mixed>
     */
    public function recordar(string $paso, ?array $datos = null): array
    {
        if ($datos !== null) {
            session()->put("instalador.{$paso}", $datos);
        }

        return session()->get("instalador.{$paso}", []);
    }

    public function olvidarTodo(): void
    {
        session()->forget('instalador');
    }

    /**
     * Si el paso previo quedó resuelto.
     *
     * Evita que alguien entre directo a «Administrador» sin haber configurado
     * la base y se lleve un error de conexión en la cara.
     */
    public function completo(string $paso): bool
    {
        return filled($this->recordar($paso));
    }

    /**
     * Deja registro de que la instalación falló, sin filtrar a la pantalla los
     * detalles del servidor.
     */
    public function registrarFalla(Throwable $error): string
    {
        report($error);

        return 'No se pudo completar la instalación. Revise que la base de datos siga disponible '
            .'y vuelva a intentarlo; el detalle quedó en el registro de errores del servidor.';
    }
}
