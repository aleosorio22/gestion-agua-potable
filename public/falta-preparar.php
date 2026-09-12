<?php

/**
 * Lo que se ve al abrir el navegador antes de instalar las dependencias.
 *
 * No puede usar nada de Laravel: se muestra justamente porque el autoloader de
 * Composer todavía no existe. Es HTML y CSS a mano, a propósito.
 */

http_response_code(503);
header('Content-Type: text/html; charset=utf-8');

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Falta preparar el sistema</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            line-height: 1.6;
            color: #1a2b33;
            background: #f4f8fa;
        }

        .tarjeta {
            max-width: 34rem;
            background: #fff;
            border: 1px solid #dde7ec;
            border-radius: .75rem;
            padding: 2rem;
        }

        h1 { font-size: 1.3rem; margin: 0 0 .5rem; }
        p { margin: 0 0 1rem; }
        .apagado { color: #5b7683; }

        pre {
            background: #1a2b33;
            color: #e8f2f6;
            padding: .9rem 1.1rem;
            border-radius: .5rem;
            overflow-x: auto;
            font-size: .95rem;
        }

        ol { padding-left: 1.2rem; }
        li { margin-bottom: .75rem; }
    </style>
</head>
<body>
    <main class="tarjeta">
        <h1>Falta preparar el sistema</h1>

        <p class="apagado">
            Se descargó el código, pero todavía no se instalaron las librerías que necesita
            para funcionar. Son dos pasos.
        </p>

        <ol>
            <li>
                Abra una terminal en la carpeta del proyecto y ejecute:
                <pre>composer preparar</pre>
            </li>
            <li>
                Vuelva a cargar esta página. El asistente de instalación va a guiarlo
                por el resto.
            </li>
        </ol>

        <p class="apagado">
            Si el comando <code>composer</code> no existe en el servidor, hay que instalarlo
            primero desde <strong>getcomposer.org</strong>.
        </p>
    </main>
</body>
</html>
