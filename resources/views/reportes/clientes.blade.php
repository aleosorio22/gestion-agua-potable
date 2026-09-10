<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Clientes</title>
    <style>
        @page {
            margin: 25px 30px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1a1a1a;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 16px;
            margin: 0 0 4px 0;
            color: #2c3e50;
        }

        .header p {
            font-size: 9px;
            color: #666;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        thead th {
            background-color: #2c3e50;
            color: #fff;
            text-align: left;
            padding: 6px 5px;
            font-size: 8.5px;
            text-transform: uppercase;
        }

        tbody td {
            padding: 5px;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
            vertical-align: top;
        }

        tbody tr:nth-child(even) {
            background-color: #f7f7f7;
        }

        .estado {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .estado-activo {
            background-color: #d4edda;
            color: #155724;
        }

        .estado-inactivo {
            background-color: #f8d7da;
            color: #721c24;
        }

        .footer {
            position: fixed;
            bottom: -15px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #999;
        }

        .resumen {
            margin-top: 12px;
            font-size: 9px;
            color: #444;
        }

        .col-codigo   { width: 8%; }
        .col-nombre   { width: 20%; }
        .col-nit      { width: 10%; }
        .col-dpi      { width: 12%; }
        .col-telefono { width: 9%; }
        .col-email    { width: 15%; }
        .col-direccion{ width: 16%; }
        .col-estado   { width: 10%; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Reporte de Clientes Registrados</h1>
        <p>Generado el {{ $fecha->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-codigo">Código</th>
                <th class="col-nombre">Nombre</th>
                <th class="col-nit">NIT</th>
                <th class="col-dpi">DPI</th>
                <th class="col-telefono">Teléfono</th>
                <th class="col-email">Email</th>
                <th class="col-direccion">Dirección de notificación</th>
                <th class="col-estado">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($clientes as $cliente)
                <tr>
                    <td>{{ $cliente->codigo }}</td>
                    <td>{{ $cliente->nombre }}</td>
                    <td>{{ $cliente->nit ?: '—' }}</td>
                    <td>{{ $cliente->dpi ?: '—' }}</td>
                    <td>{{ $cliente->telefono ?: '—' }}</td>
                    <td>{{ $cliente->email ?: '—' }}</td>
                    <td>{{ $cliente->direccion_notificacion ?: '—' }}</td>
                    <td>
                        <span class="estado estado-{{ $cliente->estado }}">
                            {{ $cliente->estado }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding: 15px;">
                        No hay clientes registrados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="resumen">
        Total de clientes: {{ $clientes->count() }}
        &nbsp;|&nbsp;
        Activos: {{ $clientes->where('estado', 'activo')->count() }}
        &nbsp;|&nbsp;
        Inactivos: {{ $clientes->where('estado', '!=', 'activo')->count() }}
    </div>

    <div class="footer">
        Sistema de Gestión de Agua Potable — Página <script type="text/php">
            if (isset($pdf)) {
                $text = "{PAGE_NUM} de {PAGE_COUNT}";
                $font = $fontMetrics->get_font("DejaVu Sans", "normal");
                $size = 8;
                $width = $fontMetrics->get_text_width($text, $font, $size);
                $x = ($pdf->get_width() - $width) / 2;
                $y = $pdf->get_height() - 20;
                $pdf->page_text($x, $y, $text, $font, $size);
            }
        </script>
    </div>

</body>
</html>
