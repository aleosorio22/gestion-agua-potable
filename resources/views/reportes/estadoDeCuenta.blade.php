<x-reportes.layout titulo="Estado de cuenta" :subtitulo="$subtitulo" :fecha="$fecha" apaisado>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Cliente</th>
                <th>Teléfono</th>
                <th class="centro">Servicios</th>
                <th>Vence lo más antiguo</th>
                <th>Estado</th>
                <th class="monto">Debe</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($clientes as $cliente)
                <tr>
                    <td>{{ $cliente->codigo }}</td>
                    <td>{{ $cliente->nombre }}</td>
                    <td>{{ $cliente->telefono ?: '—' }}</td>
                    <td class="centro">{{ $cliente->contadores_count }}</td>
                    <td>{{ $cliente->vence_mas_antigua ? \Illuminate\Support\Carbon::parse($cliente->vence_mas_antigua)->format('d/m/Y') : '—' }}</td>
                    <td>{{ $cliente->estado_de_cuenta === 'vencido' ? 'Vencido' : 'Pendiente' }}</td>
                    <td class="monto">Q{{ number_format($cliente->deuda_total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="vacio">Ningún vecino tiene saldo pendiente.</td></tr>
            @endforelse
        </tbody>
        @if ($clientes->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="6">Total adeudado</td>
                    <td class="monto">Q{{ number_format($total, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</x-reportes.layout>
