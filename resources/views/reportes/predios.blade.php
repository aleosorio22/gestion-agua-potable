<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Predios</title>
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
        td.num { text-align: center; }
        .resumen { margin-top: 12px; font-size: 9px; color: #444; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Predios</h1>
        <p>Generado el {{ $fecha->format('d/m/Y H:i') }}</p>
    </div>

    @forelse ($predios as $sectorNombre => $prediosDelSector)
        <div class="sector-titulo">{{ $sectorNombre }}</div>
        <table>
            <thead>
                <tr>
                    <th>Dirección</th>
                    <th>Contadores</th>
                    <th>Clientes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($prediosDelSector as $predio)
                    <tr>
                        <td>{{ $predio->direccion_completa ?: '—' }}</td>
                        <td class="num">{{ $predio->contadores_count }}</td>
                        <td class="num">{{ $predio->total_clientes }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p style="text-align:center; padding: 20px; color: #888;">
            No hay predios registrados.
        </p>
    @endforelse

    <div class="resumen">
        Total de predios: {{ $predios->flatten()->count() }}
        &nbsp;|&nbsp;
        Total de contadores: {{ $predios->flatten()->sum('contadores_count') }}
    </div>
</body>
</html>
