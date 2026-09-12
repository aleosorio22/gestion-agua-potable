<x-reportes.layout titulo="Boletas emitidas" :subtitulo="$subtitulo" :fecha="$fecha">
    <table>
        <thead>
            <tr>
                <th>Folio</th>
                <th>Cliente</th>
                <th class="monto">Consumo</th>
                <th class="monto">Cuota</th>
                <th class="monto">Exceso</th>
                <th class="monto">Total</th>
                <th class="monto">Saldo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($boletas as $boleta)
                <tr>
                    <td>{{ $boleta->folio }}</td>
                    <td>{{ $boleta->cliente?->nombre }}</td>
                    <td class="monto">{{ number_format((float) $boleta->consumo_m3, 2) }} m³</td>
                    <td class="monto">Q{{ number_format((float) $boleta->monto_base, 2) }}</td>
                    <td class="monto">Q{{ number_format((float) $boleta->monto_excedente, 2) }}</td>
                    <td class="monto">Q{{ number_format((float) $boleta->monto, 2) }}</td>
                    <td class="monto">Q{{ number_format($boleta->saldo, 2) }}</td>
                    <td>{{ ucfirst($boleta->estado) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="vacio">No hay boletas emitidas en este período.</td></tr>
            @endforelse
        </tbody>
        @if ($boletas->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="5">Totales</td>
                    <td class="monto">Q{{ number_format($boletas->sum(fn ($b) => (float) $b->monto), 2) }}</td>
                    <td class="monto">Q{{ number_format($boletas->sum(fn ($b) => $b->saldo), 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</x-reportes.layout>
