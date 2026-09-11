<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Boletas</title>
    <style>
        @page { margin: 25px 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
        .header h1 { font-size: 16px; margin: 0 0 4px 0; color: #2c3e50; }
        .header p { font-size: 9px; color: #666; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead th { background-color: #2c3e50; color: #fff; text-align: left; padding: 6px 5px; font-size: 8.5px; text-transform: uppercase; }
        tbody td { padding: 5px; border-bottom: 1px solid #ddd; font-size: 9px; }
        tbody tr:nth-child(even) { background-color: #f7f7f7; }
        td.monto { text-align: right; }
        .estado { padding: 2px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .estado-pagada { background-color: #d4edda; color: #155724; }
        .estado-pendiente { background-color: #fff3cd; color: #856404; }
        .estado-vencida { background-color: #f8d7da; color: #721c24; }
        .resumen { margin-top: 12px; font-size: 9px; color: #444; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Boletas</h1>
        <p>
            Período: {{ $periodo?->etiqueta_larga ?? 'Sin período vigente' }}
            &nbsp;|&nbsp; Generado el {{ $fecha->format('d/m/Y H:i') }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Cliente</th>
                <th>Consumo m³</th>
                <th>Monto</th>
                <th>Emisión</th>
                <th>Vencimiento</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($boletas as $boleta)
                <tr>
                    <td>{{ $boleta->numero }}</td>
                    <td>{{ $boleta->cliente?->nombre ?? '—' }}</td>
                    <td>{{ number_format($boleta->consumo_m3, 2) }}</td>
                    <td class="monto">Q{{ number_format($boleta->monto, 2) }}</td>
                    <td>{{ $boleta->fecha_emision?->format('d/m/Y') }}</td>
                    <td>{{ $boleta->fecha_vencimiento?->format('d/m/Y') }}</td>
                    <td><span class="estado estado-{{ $boleta->estado }}">{{ $boleta->estado }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; padding: 15px;">No hay boletas en el período vigente.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="resumen">
        Total de boletas: {{ $boletas->count() }}
        &nbsp;|&nbsp;
        Monto total: Q{{ number_format($boletas->sum('monto'), 2) }}
    </div>
</body>
</html>
