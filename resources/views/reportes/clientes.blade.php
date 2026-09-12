<x-reportes.layout titulo="Padrón de clientes" :subtitulo="$subtitulo" :fecha="$fecha">
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>DPI</th>
                <th>NIT</th>
                <th>Teléfono</th>
                <th class="centro">Servicios</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($clientes as $cliente)
                <tr>
                    <td>{{ $cliente->codigo }}</td>
                    <td>{{ $cliente->nombre }}</td>
                    <td>{{ $cliente->dpi ?: '—' }}</td>
                    <td>{{ $cliente->nit ?: '—' }}</td>
                    <td>{{ $cliente->telefono ?: '—' }}</td>
                    <td class="centro">{{ $cliente->contadores_count }}</td>
                    <td>{{ ucfirst($cliente->estado) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="vacio">Todavía no hay clientes registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-reportes.layout>
