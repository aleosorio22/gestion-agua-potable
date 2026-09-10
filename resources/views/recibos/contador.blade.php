<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo — contador {{ $contador->codigo }}</title>
    <style>
        /* Formato ticket: el ancho de 80 mm es el del rollo de la impresora
           térmica de ventanilla. En pantalla se ve igual que en papel. */
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

        .entidad { text-align: center; font-weight: bold; font-size: 15px; letter-spacing: .5px; text-transform: uppercase; margin-bottom: 2mm; }
        .ubicacion { text-align: center; text-transform: uppercase; margin-bottom: 3mm; }

        .separador { border: 0; border-top: 1px dashed #000; margin: 2.5mm 0; }

        .campo { display: flex; gap: 2mm; }
        .campo dt { min-width: 22mm; }
        .campo dd { margin: 0; flex: 1; }

        .linea { display: flex; justify-content: space-between; gap: 3mm; }
        .linea .monto { white-space: nowrap; }

        .concepto { margin-top: 1.5mm; }
        .concepto .detalle { padding-left: 3mm; }

        .servicio { font-weight: bold; text-transform: uppercase; margin-top: 3mm; }

        .lecturas { margin-top: 4mm; }
        .lecturas caption { font-weight: bold; margin-bottom: 1mm; }
        .lecturas table { width: 100%; border-collapse: collapse; }
        .lecturas td { padding: 0; }
        .lecturas .consumo { text-align: right; }

        .total { margin: 5mm 0 3mm; font-size: 17px; font-weight: bold; text-align: center; letter-spacing: .5px; }

        .pie { text-align: center; margin-top: 4mm; }
        .anulada { text-align: center; font-weight: bold; border: 1px solid #000; padding: 1.5mm; margin: 2mm 0; }

        .sin-deuda { text-align: center; margin: 8mm 0; font-weight: bold; }

        @media print {
            .no-imprimir { display: none; }
        }

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

    <dl>
        <div class="campo">
            <dt>Documento</dt>
            <dd>Detalle de cobro por servicio</dd>
        </div>
        @if ($entidad['nit'])
            <div class="campo"><dt>NIT</dt><dd>{{ $entidad['nit'] }}</dd></div>
        @endif
        @if ($entidad['telefono'])
            <div class="campo"><dt>Teléfono</dt><dd>{{ $entidad['telefono'] }}</dd></div>
        @endif
    </dl>

    <hr class="separador">

    <dl>
        <div class="campo"><dt>Contribuyente</dt><dd>{{ $contador->cliente->nombre }}</dd></div>
        <div class="campo"><dt>Código</dt><dd>{{ $contador->cliente->codigo }}</dd></div>
        @if ($contador->cliente->nit)
            <div class="campo"><dt>NIT</dt><dd>{{ $contador->cliente->nit }}</dd></div>
        @endif
        @if ($contador->cliente->dpi)
            <div class="campo"><dt>DPI</dt><dd>{{ $contador->cliente->dpi }}</dd></div>
        @endif
        <div class="campo">
            <dt>Dirección</dt>
            <dd>{{ $contador->predio?->direccion_completa ?: 'Sin dirección registrada' }}</dd>
        </div>
        @if ($contador->predio?->sector)
            <div class="campo"><dt>Sector</dt><dd>{{ $contador->predio->sector->nombre }}</dd></div>
        @endif
    </dl>

    <hr class="separador">

    <dl>
        <div class="campo"><dt>Generado</dt><dd>{{ now()->format('d/m/Y') }}</dd></div>
        @if ($vence)
            <div class="campo"><dt>Vencimiento</dt><dd>{{ $vence->format('d/m/Y') }}</dd></div>
        @endif
    </dl>

    <hr class="separador">

    @if ($boletas->isEmpty())
        <p class="sin-deuda">Este servicio no tiene saldo pendiente.</p>
    @else
        <p class="servicio linea">
            <span>Agua potable — Contador: {{ $contador->codigo }}</span>
            <span class="monto">Q.{{ number_format($total, 2) }}</span>
        </p>

        @foreach ($conceptos as $concepto)
            <div class="concepto">
                <div>{{ $concepto['concepto'] }}</div>
                <div class="linea detalle">
                    <span>{{ $concepto['periodo'] }}</span>
                    <span class="monto">Q.{{ number_format($concepto['monto'], 2) }}</span>
                </div>
            </div>
        @endforeach

        @php($ultima = $boletas->last())

        <div class="lecturas">
            <table>
                <caption>Lecturas m³</caption>
                <tbody>
                    <tr>
                        <td>Anterior</td>
                        <td>{{ number_format((float) $ultima->lectura->lectura_anterior, 0) }}</td>
                        <td class="consumo"></td>
                    </tr>
                    <tr>
                        <td>{{ $ultima->periodo->etiqueta }}</td>
                        <td>{{ number_format((float) $ultima->lectura->lectura_actual, 0) }}</td>
                        <td class="consumo">{{ number_format((float) $ultima->consumo_m3, 0) }} m³</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="total">TOTAL Q.{{ number_format($total, 2) }}</p>

        @if ($boletas->count() > 1)
            <p style="text-align:center">Incluye {{ $boletas->count() }} meses pendientes.</p>
        @endif
    @endif

    <hr class="separador">

    <div class="pie">
        <p>Folios: {{ $boletas->pluck('folio')->implode(', ') ?: '—' }}</p>
        <p>Atendido por: {{ auth()->user()->name }}</p>
        <p>Impreso {{ now()->format('d/m/Y H:i') }}</p>
    </div>

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
