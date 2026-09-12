<x-reportes.layout titulo="Cuentas por cobrar" :subtitulo="$subtitulo" :fecha="$fecha">
    <table>
        <thead>
            <tr>
                <th>Boleta</th>
                <th>Cliente</th>
                <th>Contador</th>
                <th>Período</th>
                <th>Venció</th>
                <th class="centro">Días</th>
                <th class="monto">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($boletas as $boleta)
                <tr>
                    <td>{{ $boleta->folio }}</td>
                    <td>{{ $boleta->cliente?->nombre }}</td>
                    <td>{{ $boleta->lectura?->contador?->codigo ?: '—' }}</td>
                    <td>{{ $boleta->periodo?->etiqueta }}</td>
                    <td>{{ $boleta->fecha_vencimiento?->format('d/m/Y') }}</td>
                    <td class="centro">{{ (int) $boleta->fecha_vencimiento->diffInDays(now()) }}</td>
                    <td class="monto">Q{{ number_format($boleta->saldo, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="vacio">No hay boletas vencidas. La cobranza está al día.</td></tr>
            @endforelse
        </tbody>
        @if ($boletas->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="6">Total por cobrar</td>
                    <td class="monto">Q{{ number_format($total, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</x-reportes.layout>
