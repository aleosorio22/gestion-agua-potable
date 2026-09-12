<x-reportes.layout titulo="Hoja de ruta" :subtitulo="$subtitulo" :fecha="$fecha" apaisado>
    @forelse ($contadoresPorSector as $sector => $delSector)
        <div class="grupo">{{ $sector }} — {{ $delSector->count() }} {{ $delSector->count() === 1 ? 'servicio' : 'servicios' }}</div>

        <table>
            <thead>
                <tr>
                    <th>Contador</th>
                    <th>Titular</th>
                    <th>Dirección</th>
                    <th>Paja</th>
                    <th class="monto">Lectura anterior</th>
                    {{-- En blanco a propósito: se anota a mano en la calle. --}}
                    <th class="centro" style="width: 90px">Lectura actual</th>
                    <th class="centro" style="width: 110px">Observaciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($delSector as $contador)
                    <tr>
                        <td>{{ $contador->codigo }}</td>
                        <td>{{ $contador->cliente?->nombre }}</td>
                        <td>{{ $contador->predio?->direccion_completa ?: '—' }}</td>
                        <td>{{ $contador->paja?->nombre }}</td>
                        <td class="monto">{{ number_format((float) ($contador->ultimaLectura()?->lectura_actual ?? 0), 2) }}</td>
                        <td style="height: 22px"></td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p class="vacio">No quedan contadores pendientes de lectura en este período.</p>
    @endforelse

    <div class="resumen">
        Lector: ______________________________ &nbsp;&nbsp;&nbsp;
        Fecha del recorrido: ____________________ &nbsp;&nbsp;&nbsp;
        Firma: ______________________________
    </div>
</x-reportes.layout>
