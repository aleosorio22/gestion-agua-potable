@props([
    'titulo',
    'ajustes',
])

@php
    $formato = $ajustes->formato();
    $entidad = $ajustes->entidad();
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }}</title>
    <style>
        /* El papel lo decide la oficina desde Configuración: la que compró una
           térmica de ventanilla usa rollo, la que imprime en la computadora usa
           carta u oficio. Un recibo maquetado para 80 mm sale ilegible en A4. */
        @page {
            size: {{ $formato->tamanoCss() }};
            margin: {{ $formato->margen() }};
        }

        * { box-sizing: border-box; }

        body {
            width: {{ $formato->anchoUtil() }};
            margin: 0 auto;
            padding: 4mm 0;
            font-family: {{ $formato->esRollo() ? '"Courier New", Courier, monospace' : 'Georgia, "Times New Roman", serif' }};
            font-size: {{ $formato->tamanoDeLetra() }};
            line-height: 1.45;
            color: #000;
            background: #fff;
        }

        .logo { display: block; margin: 0 auto 3mm; max-height: {{ $formato->esRollo() ? '18mm' : '26mm' }}; max-width: 60%; }

        .entidad { text-align: center; font-weight: bold; font-size: {{ $formato->esRollo() ? '15px' : '19px' }}; letter-spacing: .5px; text-transform: uppercase; }
        .ubicacion { text-align: center; text-transform: uppercase; margin-bottom: 3mm; }
        .datos-entidad { text-align: center; margin-bottom: 3mm; }

        .titulo { text-align: center; font-weight: bold; text-transform: uppercase; margin: 3mm 0 1mm; }
        .folio { text-align: center; font-size: {{ $formato->esRollo() ? '13px' : '16px' }}; font-weight: bold; margin-bottom: 2mm; }

        .separador { border: 0; border-top: 1px dashed #000; margin: 2.5mm 0; }

        .campo { display: flex; gap: 2mm; }
        .campo dt { min-width: {{ $formato->esRollo() ? '24mm' : '34mm' }}; }
        .campo dd { margin: 0; flex: 1; }

        .linea { display: flex; justify-content: space-between; gap: 3mm; }
        .linea .monto { white-space: nowrap; }

        .concepto { margin-top: 1.5mm; }
        .concepto .detalle { padding-left: 3mm; }

        .servicio { font-weight: bold; text-transform: uppercase; margin-top: 3mm; }

        .lecturas { margin-top: 4mm; }
        .lecturas caption { font-weight: bold; margin-bottom: 1mm; text-align: left; }
        .lecturas table { width: 100%; border-collapse: collapse; }
        .lecturas td { padding: 0; }
        .lecturas .consumo { text-align: right; }

        .destacado { margin: 5mm 0 3mm; font-size: {{ $formato->esRollo() ? '17px' : '22px' }}; font-weight: bold; text-align: center; letter-spacing: .5px; }
        .centrado { text-align: center; font-weight: bold; }
        .recuadro { text-align: center; font-weight: bold; border: 1px solid #000; padding: 1.5mm; margin: 2mm 0; }
        .sin-deuda { text-align: center; margin: 8mm 0; font-weight: bold; }

        .pie { text-align: center; margin-top: 4mm; }
        .pie-configurado { text-align: center; margin-top: 4mm; font-style: italic; }

        .firma { margin-top: 10mm; text-align: center; }
        .firma .linea-firma { border-top: 1px solid #000; margin: 0 {{ $formato->esRollo() ? '6mm' : '30mm' }} 1mm; }

        @media print { .no-imprimir { display: none; } }

        .no-imprimir {
            display: block;
            width: {{ $formato->anchoUtil() }};
            margin: 4mm auto;
            padding: 2mm;
            font-family: system-ui, sans-serif;
            text-align: center;
        }
    </style>
</head>
<body>
    @if ($ajustes->imprimeLogo())
        <img src="{{ $ajustes->logoEnBase64() }}" alt="" class="logo">
    @endif

    <p class="entidad">{{ $entidad['nombre'] }}</p>

    @if ($entidad['municipio'] || $entidad['departamento'])
        <p class="ubicacion">
            {{ collect([$entidad['municipio'], $entidad['departamento']])->filter()->implode(', ') }}
        </p>
    @endif

    @if ($entidad['direccion'] || $entidad['telefono'] || $entidad['nit'])
        <div class="datos-entidad">
            @if ($entidad['direccion'])<div>{{ $entidad['direccion'] }}</div>@endif
            @if ($entidad['telefono'])<div>Tel. {{ $entidad['telefono'] }}</div>@endif
            @if ($entidad['nit'])<div>NIT {{ $entidad['nit'] }}</div>@endif
        </div>
    @endif

    {{ $slot }}

    @if ($ajustes->pieDePagina())
        <hr class="separador">
        <p class="pie-configurado">{{ $ajustes->pieDePagina() }}</p>
    @endif

    <div class="no-imprimir">
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>

    <script>
        // Abre el diálogo de impresión solo, que es a lo que se entra a esta
        // página; el botón queda para reimprimir sin recargar.
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
