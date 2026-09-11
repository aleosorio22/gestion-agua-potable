<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Contadores</title>
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
        .estado { padding: 2px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .estado-activo { background-color: #d4edda; color: #155724; }
        .estado-inactivo { background-color: #f8d7da; color: #721c24; }
        .resumen { margin-top: 12px; font-size: 9px; color: #444; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Contadores</h1>
        <p>Generado el {{ $fecha->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Cliente</th>
                <th>Predio</th>
                <th>Paja</th>
                <th>Fecha instalación</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($contadores as $contador)
                <tr>
                    <td>{{ $contador->codigo }}</td>
                    <td>{{ $contador->cliente?->nombre ?? '—' }}</td>
                    <td>{{ $contador->predio?->direccion_completa ?: '—' }}</td>
                    <td>{{ $contador->paja?->nombre ?? $contador->paja_id ?? '—' }}</td>
                    <td>{{ $contador->fecha_instalacion?->format('d/m/Y') ?? '—' }}</td>
                    <td><span class="estado estado-{{ $contador->estado }}">{{ $contador->estado }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; padding: 15px;">No hay contadores registrados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="resumen">
        Total de contadores: {{ $contadores->count() }}
        &nbsp;|&nbsp;
        Activos: {{ $contadores->where('estado', 'activo')->count() }}
    </div>
</body>
</html>
