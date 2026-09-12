<x-reportes.layout titulo="Consumo por sector" :subtitulo="$subtitulo" :fecha="$fecha">
    <table>
        <thead>
            <tr>
                <th>Sector</th>
                <th class="centro">Servicios leídos</th>
                <th class="monto">Consumo total</th>
                <th class="monto">Promedio</th>
                <th class="monto">Mayor consumo</th>
                <th class="monto">% del total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sectores as $fila)
                <tr>
                    <td>{{ $fila['sector'] }}</td>
                    <td class="centro">{{ $fila['servicios'] }}</td>
                    <td class="monto">{{ number_format($fila['consumo'], 2) }} m³</td>
                    <td class="monto">{{ number_format($fila['promedio'], 2) }} m³</td>
                    <td class="monto">{{ number_format($fila['mayor'], 2) }} m³</td>
                    <td class="monto">
                        {{ $totalConsumo > 0 ? number_format($fila['consumo'] / $totalConsumo * 100, 1) : '0.0' }}%
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="vacio">No hay lecturas en este período.</td></tr>
            @endforelse
        </tbody>
        @if ($sectores->isNotEmpty())
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="centro">{{ $totalServicios }}</td>
                    <td class="monto">{{ number_format($totalConsumo, 2) }} m³</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        @endif
    </table>

    @if ($sectores->isNotEmpty())
        <div class="resumen">
            Un sector cuyo consumo sube sin que crezca el número de servicios suele indicar una fuga
            en la red, no vecinos gastando más.
        </div>
    @endif
</x-reportes.layout>
