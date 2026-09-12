<?php

/**
 * Deja un `.env` mínimo la primera vez que se abre el sistema.
 *
 * En un servidor con terminal esto lo hace `composer preparar`, que corre
 * `key:generate`. En un hosting compartido no hay terminal: el técnico sube el
 * paquete, lo descomprime y abre el navegador. Sin `.env` ni clave de
 * aplicación, Laravel revienta antes de poder dibujar el instalador, con un
 * error 500 en inglés que no dice qué hacer.
 *
 * Lo que se escribe acá es el mínimo para que el asistente pueda abrirse. Los
 * datos de verdad —base, oficina, administrador— los pide y los guarda él.
 *
 * Se incluye con `require_once` desde `bootstrap/app.php`, así que corre una
 * sola vez por proceso y antes de que Laravel lea el entorno.
 */
(static function (): void {
    $raiz = dirname(__DIR__);
    $env = $raiz.'/.env';
    $ejemplo = $raiz.'/.env.example';

    // Durante las pruebas la configuración viene de `phpunit.xml`; escribir un
    // `.env` ahí cambiaría el entorno por debajo de la suite.
    if (($_SERVER['APP_ENV'] ?? getenv('APP_ENV')) === 'testing') {
        return;
    }

    if (is_file($env) || ! is_file($ejemplo)) {
        return;
    }

    // `x` falla si el archivo ya existe: es lo que evita que dos visitas
    // simultáneas escriban dos claves distintas y se pisen las sesiones.
    $archivo = @fopen($env, 'x');

    if ($archivo === false) {
        return;
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    $hostValido = $host !== '' && preg_match('/^[A-Za-z0-9.\-:]+$/', $host) === 1;
    $seguro = ($_SERVER['HTTPS'] ?? 'off') !== 'off'
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

    $ajustes = [
        // Un paquete recién subido ya está en internet: con APP_DEBUG en
        // verdadero, cualquiera vería la contraseña de la base en la primera
        // traza de error.
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'LOG_LEVEL' => 'warning',

        // Cada instalación con su clave, o dos oficinas podrían descifrarse las
        // sesiones entre sí.
        'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),

        // La dirección por la que entró la visita, para que los enlaces salgan
        // bien desde el primer momento. Desde la terminal no hay ninguna, y el
        // instalador la corrige al final de todos modos.
        'APP_URL' => $hostValido ? ($seguro ? 'https://' : 'http://').$host : 'http://localhost',

        // La caché en base de datos necesita una tabla que todavía no existe:
        // el instalador corre antes que las migraciones.
        'CACHE_STORE' => 'file',
    ];

    $contenido = (string) file_get_contents($ejemplo);

    foreach ($ajustes as $clave => $valor) {
        $patron = '/^\s*'.preg_quote($clave, '/').'\s*=.*$/m';
        $linea = $clave.'='.$valor;

        $contenido = preg_match($patron, $contenido) === 1
            ? (string) preg_replace($patron, $linea, $contenido)
            : rtrim($contenido, "\n")."\n".$linea."\n";
    }

    fwrite($archivo, $contenido);
    fclose($archivo);

    // El `.env` guarda la contraseña de la base, y en un hosting compartido hay
    // más de un cliente en la misma máquina.
    @chmod($env, 0600);
})();
