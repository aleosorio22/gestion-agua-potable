<x-reportes.layout titulo="Predios por sector" :subtitulo="$subtitulo" :fecha="$fecha">
    @forelse ($prediosPorSector as $sector => $delSector)
        <div class="grupo">{{ $sector }} — {{ $delSector->count() }} {{ $delSector->count() === 1 ? 'predio' : 'predios' }}</div>

        <table>
            <thead>
                <tr>
                    <th>Dirección</th>
                    <th>Referencia</th>
                    <th class="centro">Contadores</th>
                    <th class="centro">Documentos</th>
                    <th>Respaldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($delSector as $predio)
                    <tr>
                        <td>{{ $predio->direccion_completa ?: 'Sin dirección registrada' }}</td>
                        <td>{{ $predio->referencia ?: '—' }}</td>
                        <td class="centro">{{ $predio->contadores_count }}</td>
                        <td class="centro">{{ $predio->documentos_count }}</td>
                        {{-- Un predio con servicio y sin escritura es el que hay
                             que salir a pedir. --}}
                        <td>{{ $predio->documentos_count > 0 ? 'Sí' : ($predio->contadores_count > 0 ? 'FALTA' : '—') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p class="vacio">Todavía no hay predios registrados.</p>
    @endforelse
</x-reportes.layout>
