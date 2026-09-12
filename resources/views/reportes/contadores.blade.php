<x-reportes.layout titulo="Contadores instalados" :subtitulo="$subtitulo" :fecha="$fecha" apaisado>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Titular</th>
                <th>Dirección</th>
                <th>Sector</th>
                <th>Paja</th>
                <th>Instalado</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($contadores as $contador)
                <tr>
                    <td>{{ $contador->codigo }}</td>
                    <td>{{ $contador->cliente?->nombre }}</td>
                    <td>{{ $contador->predio?->direccion_completa ?: '—' }}</td>
                    <td>{{ $contador->predio?->sector?->nombre ?: 'Sin sector' }}</td>
                    <td>{{ $contador->paja?->nombre }}</td>
                    <td>{{ $contador->fecha_instalacion?->format('d/m/Y') ?: '—' }}</td>
                    <td>{{ ucfirst($contador->estado) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="vacio">Todavía no hay contadores instalados.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-reportes.layout>
