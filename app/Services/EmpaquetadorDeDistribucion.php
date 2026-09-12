<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Arma el paquete que se sube a un hosting compartido.
 *
 * En un servidor propio se clona el repositorio y se corre `composer preparar`,
 * pero en un hosting compartido casi nunca hay terminal: el técnico sube un
 * archivo por el panel, lo descomprime y abre `/instalar`. Para eso el paquete
 * tiene que llevar las dependencias y los recursos ya compilados.
 *
 * El paquete no se arma comprimiendo el directorio de trabajo, sino una copia
 * limpia hecha con `git archive`: así nunca viaja el `.env` de quien empaqueta,
 * ni la carpeta privada de documentos, ni el `vendor/` con las herramientas de
 * desarrollo.
 */
class EmpaquetadorDeDistribucion
{
    /**
     * Lo que se saca de la copia limpia antes de comprimir.
     *
     * Está versionado, pero en el servidor de una oficina no hace nada: las
     * pruebas ni siquiera se pueden ejecutar sin las dependencias de
     * desarrollo, y las notas internas del equipo no son asunto de quien
     * instala el sistema.
     *
     * @var array<int, string>
     */
    private const SOBRA_EN_PRODUCCION = [
        // Desarrollo
        'tests',
        'phpunit.xml',
        '.github',
        '.editorconfig',
        '.gitattributes',
        '.gitignore',
        'deploy',

        // Notas internas y configuración de las herramientas del equipo
        '.ai',
        '.mcp.json',
        'boost.json',
        'AGENTS.md',
        'CLAUDE.md',
        'BASE-DE-DATOS-ACTUAL.md',
        'BASE-DE-DATOS-PROPUESTA.md',
    ];

    /**
     * Carpetas que el sistema necesita para arrancar y que van vacías.
     *
     * Un ZIP no guarda directorios vacíos, así que cada una viaja con un
     * `.gitkeep` adentro; sin ellas Laravel falla al primer intento de escribir
     * una sesión o un log, con una pantalla en blanco que no explica nada.
     *
     * @var array<int, string>
     */
    private const CARPETAS_DE_ESCRITURA = [
        'storage/app/private',
        'storage/app/public',
        'storage/logs',
        'storage/framework/cache/data',
        'storage/framework/sessions',
        'storage/framework/views',
        'bootstrap/cache',
    ];

    private string $raiz;

    public function __construct()
    {
        $this->raiz = base_path();
    }

    /**
     * Comprueba que el proyecto esté listo para empaquetarse.
     *
     * @return array<int, string> Lo que falta; vacío si todo está en orden.
     */
    public function loQueFalta(): array
    {
        $problemas = [];

        if (! is_file($this->raiz.'/public/build/manifest.json')) {
            $problemas[] = 'Los recursos del navegador no están compilados. Corra: npm run build';
        }

        if (! is_dir($this->raiz.'/public/js/filament')) {
            $problemas[] = 'Faltan los recursos de Filament. Corra: php artisan filament:assets';
        }

        if (! $this->existeElPrograma('git')) {
            $problemas[] = 'No se encontró git, que es con lo que se arma la copia limpia.';
        }

        if (! $this->existeElPrograma('composer')) {
            $problemas[] = 'No se encontró composer, que es con lo que se instalan las dependencias.';
        }

        if (! extension_loaded('zip')) {
            $problemas[] = 'Falta la extensión zip de PHP.';
        }

        return $problemas;
    }

    /**
     * Avisa de los cambios que no van a viajar en el paquete.
     *
     * El paquete sale de `git archive HEAD`, así que lo que está editado pero
     * sin confirmar se queda fuera. Vale la pena decirlo antes y no después.
     *
     * @return array<int, string>
     */
    public function cambiosSinConfirmar(): array
    {
        $salida = $this->correr(['git', 'status', '--porcelain'], $this->raiz);

        return collect(explode("\n", trim($salida)))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Escribe el paquete y devuelve la ruta del archivo.
     *
     * @param  (callable(string): void)|null  $informar  Para ir contando el avance.
     */
    public function empaquetar(string $destino, ?callable $informar = null): string
    {
        $informar ??= fn (string $paso) => null;

        $faltantes = $this->loQueFalta();

        if ($faltantes !== []) {
            throw new RuntimeException('El proyecto no está listo: '.implode(' ', $faltantes));
        }

        $taller = $this->raiz.'/storage/app/empaquetado-'.Str::random(8);

        try {
            File::ensureDirectoryExists($taller);

            $informar('Copiando los archivos versionados');
            $this->copiarElProyecto($taller);

            $informar('Quitando lo que no se usa en producción');
            $this->quitarLoQueSobra($taller);

            $informar('Instalando dependencias de producción (esto tarda)');
            $this->instalarDependencias($taller);

            $informar('Copiando los recursos compilados');
            $this->copiarRecursosCompilados($taller);

            $informar('Preparando las carpetas de escritura');
            $this->prepararCarpetasDeEscritura($taller);

            $informar('Escribiendo las instrucciones');
            File::put($taller.'/LEEME.txt', $this->instrucciones());
            File::put($taller.'/.htaccess', $this->htaccessDeRaiz());

            $informar('Comprimiendo');
            $archivos = $this->comprimir($taller, $destino);

            $informar(number_format($archivos).' archivos, '.$this->peso($destino));
        } finally {
            File::deleteDirectory($taller);
        }

        return $destino;
    }

    /**
     * Vuelca en el taller sólo lo que está versionado.
     *
     * `git archive` respeta el `.gitignore`, que es justo lo que se necesita:
     * el `.env`, el `vendor/` local y la carpeta privada de documentos quedan
     * fuera sin tener que enumerarlos.
     */
    private function copiarElProyecto(string $taller): void
    {
        $paquete = $taller.'/proyecto.tar';

        $this->correr(['git', 'archive', '--format=tar', '--output='.$paquete, 'HEAD'], $this->raiz);
        $this->correr(['tar', '-xf', $paquete, '-C', $taller], $this->raiz);

        File::delete($paquete);
    }

    private function quitarLoQueSobra(string $taller): void
    {
        foreach (self::SOBRA_EN_PRODUCCION as $ruta) {
            $completa = $taller.'/'.$ruta;

            if (is_dir($completa)) {
                File::deleteDirectory($completa);

                continue;
            }

            File::delete($completa);
        }
    }

    /**
     * Deja el `vendor/` listo para producción dentro del taller.
     *
     * Los scripts de composer llaman a artisan, y artisan no arranca sin un
     * `.env`. Se le pone uno de paso y se borra enseguida: el `.env` de verdad
     * lo escribe el instalador en el servidor, con los datos de esa oficina.
     */
    private function instalarDependencias(string $taller): void
    {
        File::copy($taller.'/.env.example', $taller.'/.env');

        $this->correr([
            'composer', 'install',
            '--no-dev',
            '--no-interaction',
            '--prefer-dist',
            '--optimize-autoloader',
        ], $taller, minutos: 10);

        File::delete($taller.'/.env');
    }

    /**
     * Trae lo que produce `npm run build`, que no está versionado.
     */
    private function copiarRecursosCompilados(string $taller): void
    {
        File::copyDirectory($this->raiz.'/public/build', $taller.'/public/build');

        // `.vite` guarda el manifiesto interno del compilador; el que usa
        // Laravel es `manifest.json`, que está un nivel arriba.
        File::deleteDirectory($taller.'/public/build/.vite');
    }

    private function prepararCarpetasDeEscritura(string $taller): void
    {
        foreach (self::CARPETAS_DE_ESCRITURA as $carpeta) {
            File::ensureDirectoryExists($taller.'/'.$carpeta);
            File::put($taller.'/'.$carpeta.'/.gitkeep', '');
        }

        // Un ZIP hecho en macOS o Linux conserva los permisos, y los de
        // escritura son la causa número uno de un error 500 recién instalado.
        $this->correr(['chmod', '-R', 'ug+rwX', $taller.'/storage', $taller.'/bootstrap/cache'], $taller);
    }

    /**
     * Comprime el taller sin carpeta contenedora y devuelve cuántos archivos entraron.
     *
     * Sin carpeta contenedora porque el técnico casi siempre descomprime dentro
     * de la carpeta que ya creó; un nivel de más lo obliga a mover todo a mano.
     */
    private function comprimir(string $taller, string $destino): int
    {
        File::ensureDirectoryExists(dirname($destino));

        $zip = new ZipArchive;

        if ($zip->open($destino, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("No se pudo crear el archivo en {$destino}.");
        }

        $archivos = 0;

        /** @var iterable<SplFileInfo> $encontrados */
        $encontrados = File::allFiles($taller, hidden: true);

        foreach ($encontrados as $archivo) {
            $zip->addFile($archivo->getPathname(), $archivo->getRelativePathname());
            $archivos++;
        }

        $zip->close();

        return $archivos;
    }

    private function existeElPrograma(string $programa): bool
    {
        return (new Process(['which', $programa]))->run() === 0;
    }

    /**
     * @param  array<int, string>  $comando
     */
    private function correr(array $comando, string $dentroDe, int $minutos = 2): string
    {
        $proceso = new Process($comando, $dentroDe, timeout: $minutos * 60);
        $proceso->run();

        if (! $proceso->isSuccessful()) {
            throw new RuntimeException(
                'Falló «'.implode(' ', $comando).'»: '.trim($proceso->getErrorOutput() ?: $proceso->getOutput())
            );
        }

        return $proceso->getOutput();
    }

    private function peso(string $ruta): string
    {
        return number_format(((int) filesize($ruta)) / 1024 / 1024, 1).' MB';
    }

    /**
     * Reescritura hacia `public/` para cuando el dominio apunta a la raíz.
     *
     * Muchos hostings compartidos no dejan mover la carpeta del dominio. Sin
     * esto el visitante vería el listado de archivos del proyecto, incluido el
     * `.env` que escribe el instalador.
     */
    public function htaccessDeRaiz(): string
    {
        return <<<'HTACCESS'
        # Manda todo a public/, que es lo unico que debe verse desde la web.
        #
        # Si su hosting le permite apuntar el dominio directamente a la carpeta
        # "public", hagalo y borre este archivo: esa es la forma recomendada.
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteRule ^(?!public/)(.*)$ public/$1 [L]
        </IfModule>

        # Por si el servidor no tiene mod_rewrite: que al menos no liste nada
        # ni entregue los archivos con la configuracion.
        Options -Indexes

        <FilesMatch "^(\.env.*|composer\.(json|lock)|artisan|package.*\.json)$">
            Require all denied
        </FilesMatch>
        HTACCESS;
    }

    public function instrucciones(): string
    {
        return <<<'TEXTO'
        SISTEMA DE GESTION DE AGUA POTABLE
        Instalacion en hosting compartido
        ==================================

        Este paquete ya trae todo lo necesario. No hace falta usar la terminal.

        Requisitos del servidor: PHP 8.3 o mas nuevo y una base de datos MySQL
        o MariaDB. La version de PHP suele elegirse desde el panel del hosting.


        1. CREE LA BASE DE DATOS
        ------------------------
        Desde el panel de su hosting (cPanel, Plesk o similar):

          - Cree una base de datos nueva y vacia.
          - Cree un usuario y asignele todos los permisos sobre esa base.
          - Anote el nombre de la base, el usuario y la contrasena:
            se los va a pedir el instalador.

        El servidor de base de datos suele ser "localhost". Si su hosting le
        indica otro, use ese.


        2. SUBA Y DESCOMPRIMA ESTE ARCHIVO
        ----------------------------------
        Hay dos formas, segun lo que permita su hosting:

        FORMA A (recomendada, y mas segura)
          Suba el archivo a una carpeta fuera de la carpeta publica, por
          ejemplo "agua-potable", descomprimalo ahi, y despues apunte el
          dominio a la subcarpeta "agua-potable/public" desde el panel.

        FORMA B (si no puede cambiar la carpeta del dominio)
          Suba y descomprima el archivo directamente dentro de "public_html".
          El archivo .htaccess incluido se encarga del resto.

        Descomprima siempre desde el panel del hosting, no suba las carpetas
        una por una: son miles de archivos y por FTP casi siempre falla alguno.


        3. REVISE LOS PERMISOS
        ----------------------
        Estas dos carpetas tienen que permitir escritura (755 o 775):

          storage
          bootstrap/cache

        En el administrador de archivos: clic derecho > Permisos, y marque
        que se apliquen tambien a las subcarpetas.


        4. ABRA EL INSTALADOR
        ---------------------
        En su navegador entre a:

            https://SU-DOMINIO/instalar

        El asistente revisa el servidor, le pide los datos de la base, los
        datos de la oficina y la cuenta del administrador. Al terminar, el
        sistema queda listo para usarse y el instalador se cierra solo.


        SI ALGO NO FUNCIONA
        -------------------
        - "Falta preparar el sistema": el paquete se descomprimio incompleto.
          Vuelva a subirlo y descomprimalo desde el panel.

        - Pantalla en blanco o error 500: casi siempre son los permisos del
          punto 3. Si no, revise el archivo storage/logs/laravel.log.

        - El instalador dice que falta una extension de PHP: pidale a su
          proveedor que la active. La pantalla le dice cual es y para que se
          usa.

        - Se ve el listado de archivos en vez del sistema: el dominio esta
          apuntando a la carpeta equivocada. Debe apuntar a "public".
        TEXTO;
    }
}
