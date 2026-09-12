<x-reportes.layout titulo="Contadores que faltan por leer" :subtitulo="$subtitulo" :fecha="$fecha">
    <table>
        <thead>
            <tr>
                <th>Contador</th>
                <th>Titular</th>
                <th>Dirección</th>
                <th>Sector</th>
                <th class="monto">Última lectura</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($contadores as $contador)
                <tr>
                    <td>{{ $contador->codigo }}</td>
                    <td>{{ $contador->cliente?->nombre }}</td>
                    <td>{{ $contador->predio?->direccion_completa ?: '—' }}</td>
                    <td>{{ $contador->predio?->sector?->nombre ?: 'Sin sector' }}</td>
                    <td class="monto">{{ number_format((float) ($contador->ultimaLectura()?->lectura_actual ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="vacio">El recorrido está completo: no falta ningún contador por leer.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($total > 0)
        <div class="resumen">
            <strong>Avance del recorrido:</strong> {{ $leidos }} de {{ $total }} contadores leídos.
            @if ($contadores->isNotEmpty())
                Cada contador sin leer es agua entregada que nadie va a cobrar este mes.
            @endif
        </div>
    @endif
</x-reportes.layout>
