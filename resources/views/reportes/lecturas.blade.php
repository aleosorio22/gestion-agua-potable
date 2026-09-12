<x-reportes.layout titulo="Lecturas tomadas" :subtitulo="$subtitulo" :fecha="$fecha">
    @forelse ($lecturasPorSector as $sector => $delSector)
        <div class="grupo">{{ $sector }}</div>

        <table>
            <thead>
                <tr>
                    <th>Contador</th>
                    <th>Titular</th>
                    <th>Visita</th>
                    <th class="monto">Anterior</th>
                    <th class="monto">Actual</th>
                    <th class="monto">Consumo</th>
                    <th>Lector</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($delSector as $lectura)
                    <tr>
                        <td>{{ $lectura->contador?->codigo }}</td>
                        <td>{{ $lectura->contador?->cliente?->nombre }}</td>
                        <td>{{ $lectura->fecha_lectura?->format('d/m/Y') }}</td>
                        <td class="monto">{{ number_format((float) $lectura->lectura_anterior, 2) }}</td>
                        <td class="monto">{{ number_format((float) $lectura->lectura_actual, 2) }}</td>
                        <td class="monto">{{ number_format((float) $lectura->consumo_m3, 2) }} m³</td>
                        <td>{{ $lectura->usuario?->name }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">Consumo del sector</td>
                    <td class="monto">{{ number_format($delSector->sum(fn ($l) => (float) $l->consumo_m3), 2) }} m³</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @empty
        <p class="vacio">No hay lecturas registradas en este período.</p>
    @endforelse
</x-reportes.layout>
