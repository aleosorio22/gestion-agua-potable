<?php

use App\Services\EmpaquetadorDeDistribucion;

/**
 * El paquete en sí se arma corriendo `php artisan app:empaquetar`, que tarda
 * varios minutos porque instala dependencias. Lo que se prueba acá es lo que
 * puede romperse en silencio y sólo se notaría en el servidor de una oficina.
 */
it('reconoce que el proyecto está listo para empaquetarse', function () {
    // Si esto falla, el paquete saldría sin recursos compilados y el sistema
    // se vería sin estilos. Es la comprobación que corre antes de armar nada.
    expect((new EmpaquetadorDeDistribucion)->loQueFalta())->toBe([]);
});

it('deja fuera del paquete el .env y las notas internas del equipo', function () {
    $sobra = constanteDelEmpaquetador('SOBRA_EN_PRODUCCION');

    // `git archive` ya excluye lo ignorado; esto cubre lo que sí está
    // versionado y no debe salir del equipo.
    expect($sobra)->toContain('.ai', 'CLAUDE.md', 'tests');

    // Y lo que nunca debe estar en la lista, porque el sistema no arranca sin ello.
    expect($sobra)->not->toContain('vendor', 'public', 'config', 'bootstrap', '.env.example');
});

it('crea todas las carpetas que el sistema necesita escribir', function () {
    $carpetas = constanteDelEmpaquetador('CARPETAS_DE_ESCRITURA');

    // Un ZIP no guarda carpetas vacías: si alguna falta, la primera visita
    // termina en error 500 sin explicación.
    expect($carpetas)->toContain(
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/framework/cache/data',
        'storage/logs',
        'storage/app/private',
        'bootstrap/cache',
    );
});

it('el .htaccess de la raíz esconde la configuración cuando el dominio no apunta a public', function () {
    $htaccess = (new EmpaquetadorDeDistribucion)->htaccessDeRaiz();

    expect($htaccess)
        ->toContain('RewriteRule ^(?!public/)(.*)$ public/$1')
        ->toContain('Options -Indexes')
        // El `.env` que escribe el instalador lleva la contraseña de la base.
        ->toMatch('/FilesMatch.*\\\\\.env/');
});

it('las instrucciones le dicen al técnico a dónde entrar y qué permisos poner', function () {
    $leeme = (new EmpaquetadorDeDistribucion)->instrucciones();

    expect($leeme)
        ->toContain('/instalar')
        ->toContain('storage')
        ->toContain('bootstrap/cache');
});

/**
 * @return array<int, string>
 */
function constanteDelEmpaquetador(string $nombre): array
{
    return (new ReflectionClass(EmpaquetadorDeDistribucion::class))->getConstant($nombre);
}
