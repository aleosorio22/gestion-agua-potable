<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo de pago {{ $pago->folio }}</title>
    <style>
        /* Mismo formato de ticket que la boleta: el rollo de la impresora
           térmica de ventanilla es de 80 mm. */
        @page { size: 80mm auto; margin: 4mm; }

        * { box-sizing: border-box; }

        body {
            width: 72mm;
            margin: 0 auto;
            padding: 4mm 0;
            font-family: "Courier New", Courier, monospace;
            font-size: 10.5px;
            line-height: 1.45;
            color: #000;
            background: #fff;
        }

        .entidad { text-align: center; font-weight: bold; font-size: 15px; letter-spacing: .5px; text-transform: uppercase; }
        .ubicacion { text-align: center; text-transform: uppercase; margin-bottom: 3mm; }
        .titulo { text-align: center; font-weight: bold; text-transform: uppercase; margin: 3mm 0 1mm; }
        .folio { text-align: center; font-size: 13px; font-weight: bold; margin-bottom: 2mm; }

        .separador { border: 0; border-top: 1px dashed #000; margin: 2.5mm 0; }

        .campo { display: flex; gap: 2mm; }
        .campo dt { min-width: 24mm; }
        .campo dd { margin: 0; flex: 1; }

        .linea { display: flex; justify-content: space-between; gap: 3mm; }
        .linea .monto { white-space: nowrap; }

        .recibido { margin: 5mm 0 3mm; font-size: 17px; font-weight: bold; text-align: center; letter-spacing: .5px; }
        .saldo { text-align: center; font-weight: bold; }
        .saldado { text-align: center; font-weight: bold; border: 1px solid #000; padding: 1.5mm; margin: 2mm 0; }

        .pie { text-align: center; margin-top: 4mm; }
        .firma { margin-top: 10mm; text-align: center; }
        .firma .linea-firma { border-top: 1px solid #000; margin: 0 6mm 1mm; }

        @media print { .no-imprimir { display: none; } }

        .no-imprimir {
            display: block;
            width: 72mm;
            margin: 4mm auto;
            padding: 2mm;
            font-family: system-ui, sans-serif;
            text-align: center;
        }
    </style>
</head>
<body>
    <p class="entidad">{{ $entidad['nombre'] }}</p>

    @if ($entidad['municipio'] || $entidad['departamento'])
        <p class="ubicacion">
            {{ collect([$entidad['municipio'], $entidad['departamento']])->filter()->implode(', ') }}
        </p>
    @endif

    <p class="titulo">Recibo de pago</p>
    <p class="folio">{{ $pago->folio }}</p>

    <hr class="separador">

    <dl>
        <div class="campo"><dt>Recibí de</dt><dd>{{ $boleta->cliente->nombre }}</dd></div>
        <div class="campo"><dt>Código</dt><dd>{{ $boleta->cliente->codigo }}</dd></div>
        @if ($boleta->lectura?->contador)
            <div class="campo"><dt>Contador</dt><dd>{{ $boleta->lectura->contador->codigo }}</dd></div>
        @endif
        <div class="campo"><dt>Fecha</dt><dd>{{ $pago->fecha_pago->format('d/m/Y') }}</dd></div>
    </dl>

    <hr class="separador">

    <dl>
        <div class="campo"><dt>Concepto</dt><dd>Servicio de agua potable</dd></div>
        <div class="campo"><dt>Boleta</dt><dd>{{ $boleta->folio }}</dd></div>
        <div class="campo"><dt>Período</dt><dd>{{ $boleta->periodo->etiqueta_larga }}</dd></div>
        <div class="campo"><dt>Método</dt><dd>{{ $pago->metodoPago->nombre }}</dd></div>
        @if ($pago->referencia)
            <div class="campo"><dt>Referencia</dt><dd>{{ $pago->referencia }}</dd></div>
        @endif
    </dl>

    <hr class="separador">

    <p class="linea">
        <span>Total de la boleta</span>
        <span class="monto">Q.{{ number_format((float) $boleta->monto, 2) }}</span>
    </p>

    <p class="recibido">RECIBÍ Q.{{ number_format((float) $pago->monto, 2) }}</p>

    @if ($saldo > 0)
        {{-- Un abono parcial tiene que decirlo en el papel: si no, el vecino se
             va creyendo que quedó al día. --}}
        <p class="saldo">Saldo pendiente: Q.{{ number_format($saldo, 2) }}</p>
    @else
        <p class="saldado">BOLETA SALDADA</p>
    @endif

    <hr class="separador">

    <div class="pie">
        <p>Atendido por: {{ $pago->usuario->name }}</p>
        <p>Impreso {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="firma">
        <div class="linea-firma"></div>
        <p>Firma y sello de la oficina</p>
    </div>

    <div class="no-imprimir">
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
