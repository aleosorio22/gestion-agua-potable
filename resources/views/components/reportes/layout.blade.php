@props([
    'titulo',
    'fecha',
    'subtitulo' => null,
    'apaisado' => false,
])

@php
    $ajustes = app(App\Support\AjustesDeImpresion::class);
    $entidad = $ajustes->entidad();
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        /* dompdf necesita DejaVu Sans para las tildes y la ñ: con la fuente
           por defecto salen como cuadros. */
        @page { size: {{ $apaisado ? 'landscape' : 'portrait' }}; margin: 25px 30px 45px; }

        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; }

        .membrete { text-align: center; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 15px; }
        .membrete .entidad { font-size: 13px; font-weight: bold; color: #2c3e50; text-transform: uppercase; }
        .membrete .ubicacion { font-size: 8.5px; color: #666; }
        .membrete h1 { font-size: 15px; margin: 8px 0 3px; color: #2c3e50; }
        .membrete .subtitulo { font-size: 9.5px; color: #444; margin: 0; }
        .membrete .generado { font-size: 8px; color: #888; margin-top: 4px; }

        .logo { max-height: 42px; margin-bottom: 6px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead th {
            background-color: #2c3e50;
            color: #fff;
            text-align: left;
            padding: 6px 5px;
            font-size: 8.5px;
            text-transform: uppercase;
        }
        tbody td { padding: 5px; border-bottom: 1px solid #ddd; font-size: 9px; }
        tbody tr:nth-child(even) { background-color: #f7f7f7; }

        td.monto, th.monto { text-align: right; }
        td.centro, th.centro { text-align: center; }

        tfoot td { padding: 6px 5px; font-weight: bold; border-top: 2px solid #2c3e50; font-size: 9.5px; }

        .grupo { margin-top: 14px; font-size: 11px; font-weight: bold; color: #2c3e50; border-bottom: 1px solid #bdc3c7; padding-bottom: 3px; }

        .resumen { margin-top: 14px; font-size: 9px; color: #444; border-top: 1px solid #ddd; padding-top: 8px; }
        .resumen strong { color: #2c3e50; }

        .vacio { text-align: center; padding: 25px; color: #888; font-style: italic; }

        /* El pie se repite en todas las páginas: un reporte de varias hojas
           suelto sobre un escritorio no dice de dónde salió ni cuándo. */
        .pie {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            font-size: 7.5px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 4px;
        }
        .pie .izquierda { float: left; }
        .pie .derecha { float: right; }
    </style>
</head>
<body>
    <div class="pie">
        <span class="izquierda">{{ $entidad['nombre'] }} — {{ $titulo }}</span>
        <span class="derecha">{{ $fecha->format('d/m/Y H:i') }}</span>
    </div>

    <div class="membrete">
        @if ($ajustes->imprimeLogo())
            <img src="{{ $ajustes->logoEnBase64() }}" alt="" class="logo">
        @endif

        <div class="entidad">{{ $entidad['nombre'] }}</div>

        @if ($entidad['municipio'] || $entidad['departamento'])
            <div class="ubicacion">
                {{ collect([$entidad['municipio'], $entidad['departamento']])->filter()->implode(', ') }}
            </div>
        @endif

        <h1>{{ $titulo }}</h1>

        @if ($subtitulo)
            <p class="subtitulo">{{ $subtitulo }}</p>
        @endif

        <p class="generado">Generado el {{ $fecha->format('d/m/Y') }} a las {{ $fecha->format('H:i') }}</p>
    </div>

    {{ $slot }}
</body>
</html>
