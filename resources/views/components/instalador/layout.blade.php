@props([
    'paso' => 1,
    'titulo',
    'descripcion' => null,
])

@php
    $pasos = [
        1 => 'Bienvenida',
        2 => 'Requisitos',
        3 => 'Base de datos',
        4 => 'La oficina',
        5 => 'Administrador',
    ];
@endphp

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }} — Instalación</title>
    <style>
        :root {
            --tinta: #1a2b33;
            --suave: #5b7683;
            --linea: #dde7ec;
            --fondo: #f4f8fa;
            --agua: #0e7490;
            --agua-clara: #e0f2f7;
            --bien: #15803d;
            --mal: #b91c1c;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100%;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: var(--tinta);
            background: var(--fondo);
        }

        .lienzo { max-width: 46rem; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }

        header h1 { font-size: 1.5rem; margin: 0 0 .35rem; letter-spacing: -.01em; }
        header p { margin: 0; color: var(--suave); }

        /* El indicador dice cuántos pasos faltan: sin él, quien instala no
           sabe si está en el segundo de tres o de diez. */
        .pasos { display: flex; flex-wrap: wrap; gap: .4rem; margin: 1.75rem 0; padding: 0; list-style: none; }
        .pasos li {
            flex: 1 1 6rem;
            padding: .5rem .6rem;
            border-top: 3px solid var(--linea);
            font-size: .8rem;
            color: var(--suave);
        }
        .pasos li.activo { border-top-color: var(--agua); color: var(--agua); font-weight: 600; }
        .pasos li.hecho { border-top-color: var(--agua); color: var(--tinta); }

        .tarjeta {
            background: #fff;
            border: 1px solid var(--linea);
            border-radius: .75rem;
            padding: 1.75rem;
        }

        h2 { font-size: 1.15rem; margin: 0 0 .35rem; }
        .ayuda { color: var(--suave); margin: 0 0 1.5rem; }

        .campo { margin-bottom: 1.15rem; }
        .campo label { display: block; font-weight: 600; font-size: .9rem; margin-bottom: .35rem; }
        .campo .nota { display: block; font-weight: 400; color: var(--suave); font-size: .85rem; margin-top: .3rem; }

        input[type="text"], input[type="number"], input[type="email"], input[type="password"], input[type="file"], select {
            width: 100%;
            padding: .65rem .75rem;
            border: 1px solid var(--linea);
            border-radius: .5rem;
            font: inherit;
            font-size: .95rem;
            background: #fff;
            color: var(--tinta);
        }

        input:focus, select:focus { outline: 2px solid var(--agua); outline-offset: 1px; border-color: var(--agua); }

        .dupla { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 34rem) { .dupla { grid-template-columns: 1fr; } }

        .acciones { display: flex; gap: .75rem; align-items: center; margin-top: 1.75rem; }
        .acciones .atras { margin-right: auto; }

        .boton {
            display: inline-block;
            padding: .7rem 1.35rem;
            border: 0;
            border-radius: .5rem;
            background: var(--agua);
            color: #fff;
            font: inherit;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .boton:hover { filter: brightness(1.08); }
        .boton[disabled] { background: var(--linea); color: var(--suave); cursor: not-allowed; }
        .boton.discreto { background: transparent; color: var(--suave); font-weight: 500; padding-left: 0; }

        .aviso { border-radius: .5rem; padding: .9rem 1rem; margin-bottom: 1.5rem; }
        .aviso.error { background: #fef2f2; border: 1px solid #fecaca; color: var(--mal); }
        .aviso ul { margin: .35rem 0 0; padding-left: 1.1rem; }

        .grupo-requisitos { margin-bottom: 1.5rem; }
        .grupo-requisitos h3 { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: var(--suave); margin: 0 0 .5rem; }

        .revision { list-style: none; margin: 0; padding: 0; }
        .revision li {
            display: flex;
            gap: .75rem;
            align-items: baseline;
            padding: .6rem .75rem;
            border: 1px solid var(--linea);
            border-radius: .5rem;
            margin-bottom: .4rem;
            background: #fff;
        }
        .revision .marca { font-weight: 700; width: 1.1rem; flex: none; }
        .revision .marca.si { color: var(--bien); }
        .revision .marca.no { color: var(--mal); }
        .revision .que { font-weight: 600; font-size: .92rem; }
        .revision .detalle { color: var(--suave); font-size: .88rem; }
        .revision li.falla { border-color: #fecaca; background: #fef2f2; }

        .resumen { background: var(--agua-clara); border-radius: .5rem; padding: 1rem 1.15rem; margin-bottom: 1.5rem; }
        .resumen p { margin: 0; }

        .lista-clara { margin: 0 0 1.5rem; padding-left: 1.15rem; color: var(--suave); }
        .lista-clara li { margin-bottom: .3rem; }

        .credenciales { background: var(--fondo); border: 1px solid var(--linea); border-radius: .5rem; padding: 1rem 1.15rem; margin: 1.25rem 0; }
        .credenciales dt { font-size: .8rem; color: var(--suave); }
        .credenciales dd { margin: 0 0 .6rem; font-weight: 600; }

        .casilla { display: flex; gap: .6rem; align-items: flex-start; }
        .casilla input { margin-top: .3rem; }
    </style>
</head>
<body>
    <div class="lienzo">
        <header>
            <h1>Instalación del sistema de agua potable</h1>
            <p>Cinco pasos para dejarlo funcionando.</p>
        </header>

        <ol class="pasos">
            @foreach ($pasos as $numero => $nombre)
                <li class="{{ $numero === $paso ? 'activo' : ($numero < $paso ? 'hecho' : '') }}">
                    {{ $numero }}. {{ $nombre }}
                </li>
            @endforeach
        </ol>

        <main class="tarjeta">
            <h2>{{ $titulo }}</h2>
            @if ($descripcion)
                <p class="ayuda">{{ $descripcion }}</p>
            @endif

            @if ($errors->any())
                <div class="aviso error">
                    <strong>Hay algo que corregir:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
