<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Lecturas</title>
    <style>
        @page { margin: 25px 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
        .header h1 { font-size: 16px; margin: 0 0 4px 0; color: #2c3e50; }
        .header p { font-size: 9px; color: #666; margin: 0; }
        .sector-titulo {
            background-color: #34495e; color: #fff; padding: 5px 8px;
            font-size: 10px; font-weight: bold; margin-top: 14px; text-transform: uppercase;
        }
        table { width: 100%; border-collapse: collapse; }
        thead th { background-color: #2c3e50; color: #fff; text-align: left; padding: 6px 5px; font-size: 8.5px; text-transform: uppercase; }
        tbody td { padding: 5px; border-bottom: 1px solid #ddd; font-size: 9px; }
        tbody tr:nth-child(even) { background-color: #f7f7f7; }
        td.num { text-align: right; }
        .resumen { margin-top: 12px; font-size: 9px; color: #444; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Lecturas</h1>
        <p>
            Período: {{ $periodo?->etiqueta_larga ?? 'Sin período vigente' }}
            &nbsp;|&nbsp; Generado el {{ $fecha->format('d/m/Y H:i') }}
        </p>
    </div>

    @forelse ($lecturasPorSector as $sectorNombre => $lecturas)
        <div class="sector-titulo">{{ $sectorNombre }}</div>
        <table>
            <thead>
                <tr>
                    <th>Contador</th>
                    <th>Cliente</th>
                    <th>Lect. anterior</th>
                    <th>Lect. actual</th>
                    <th>Consumo m³</th>
                    <th>Fecha</th>
                    <th>Lector</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lecturas as $lectura)
                    <tr>
                        <td>{{ $lectura->contador?->codigo ?? '—' }}</td>
                        <td>{{ $lectura->contador?->cliente?->nombre ?? '—' }}</td>
                        <td class="num">{{ number_format($lectura->lectura_anterior, 2) }}</td>
                        <td class="num">{{ number_format($lectura->lectura_actual, 2) }}</td>
                        <td class="num">{{ number_format($lectura->consumo_m3, 2) }}</td>
                        <td>{{ $lectura->fecha_lectura?->format('d/m/Y') }}</td>
                        <td>{{ $lectura->usuario?->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p style="text-align:center; padding: 20px; color: #888;">
            No hay lecturas registradas en el período vigente.
        </p>
    @endforelse

    <div class="resumen">
        Total de lecturas: {{ $lecturasPorSector->flatten()->count() }}
        &nbsp;|&nbsp;
        Consumo total: {{ number_format($lecturasPorSector->flatten()->sum('consumo_m3'), 2) }} m³
    </div>
</body>
</html>
