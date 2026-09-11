<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cuentas por Cobrar</title>
    <style>
        @page { margin: 25px 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
        .header h1 { font-size: 16px; margin: 0 0 4px 0; color: #2c3e50; }
        .header p { font-size: 9px; color: #666; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead th { background-color: #721c24; color: #fff; text-align: left; padding: 6px 5px; font-size: 8.5px; text-transform: uppercase; }
        tbody td { padding: 5px; border-bottom: 1px solid #ddd; font-size: 9px; }
        tbody tr:nth-child(even) { background-color: #f7f7f7; }
        td.monto { text-align: right; }
        td.atraso { text-align: center; font-weight: bold; }
        .resumen { margin-top: 12px; font-size: 9px; color: #444; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Cuentas por Cobrar (Mora)</h1>
        <p>Generado el {{ $fecha->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No. Boleta</th>
                <th>Cliente</th>
                <th>Período</th>
                <th>Saldo</th>
                <th>Vencimiento</th>
                <th>Días de atraso</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($boletas as $boleta)
                <tr>
                    <td>{{ $boleta->numero }}</td>
                    <td>{{ $boleta->cliente?->nombre ?? '—' }}</td>
                    <td>{{ $boleta->periodo?->etiqueta ?? '—' }}</td>
                    <td class="monto">Q{{ number_format($boleta->saldo, 2) }}</td>
                    <td>{{ $boleta->fecha_vencimiento?->format('d/m/Y') }}</td>
                    <td class="atraso">{{ $boleta->dias_atraso }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; padding: 15px;">No hay cuentas en mora.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="resumen">
        Total de boletas vencidas: {{ $boletas->count() }}
        &nbsp;|&nbsp;
        Saldo total en mora: Q{{ number_format($boletas->sum('saldo'), 2) }}
    </div>
</body>
</html>
