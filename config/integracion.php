<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Token de la API de integración
    |--------------------------------------------------------------------------
    |
    | Secreto compartido entre el monolito, el microservicio despachador y la
    | Lambda que devuelve los resultados. La API de integración no es pública
    | —Nginx solo la abre dentro de la VPC—, pero el token es la segunda
    | cerradura: aunque alguien alcance la URL, sin él recibe 401.
    |
    | Se genera con: php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
    |
    */

    'token' => env('INTEGRACION_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Prefijo de la llave de idempotencia
    |--------------------------------------------------------------------------
    |
    | La llave con la que cada pago se identifica ante el banco y el ERP tiene
    | la forma PREFIJO-000123. El prefijo identifica al equipo y lo asigna el
    | docente; debe coincidir con la API key de los simuladores.
    |
    */

    'prefijo' => env('INTEGRACION_PREFIJO', 'EQX'),

    /*
    |--------------------------------------------------------------------------
    | Minutos para volver a reclamar un envío encolado
    |--------------------------------------------------------------------------
    |
    | Si el producer se cae entre reclamar un pago y publicarlo en la cola, ese
    | envío quedaría en ENCOLADO para siempre. Pasado este tiempo sin resultado
    | se vuelve a entregar. Es seguro porque la llave de idempotencia no cambia:
    | si el primer intento sí llegó, el destino responde «duplicado» y cuenta
    | como entregado.
    |
    */

    'minutos_reclamo' => (int) env('INTEGRACION_MINUTOS_RECLAMO', 30),

    /*
    |--------------------------------------------------------------------------
    | Tope de pagos por reclamo
    |--------------------------------------------------------------------------
    |
    | Techo del parámetro `limite`. Evita que una llamada pida el padrón
    | entero y arme una respuesta de varios megabytes.
    |
    */

    'limite_maximo' => (int) env('INTEGRACION_LIMITE_MAXIMO', 100),

];
