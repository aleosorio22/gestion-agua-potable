<x-reportes.layout titulo="Corte de caja" :subtitulo="$subtitulo" :fecha="$fecha">
    @forelse ($porMetodo as $metodo => $delMetodo)
        <div class="grupo">{{ $metodo }} — {{ $delMetodo->count() }} {{ $delMetodo->count() === 1 ? 'cobro' : 'cobros' }}</div>

        <table>
            <thead>
                <tr>
                    <th>Recibo</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Boleta</th>
                    <th>Referencia</th>
                    <th>Cobró</th>
                    <th class="monto">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($delMetodo as $pago)
                    <tr>
                        <td>{{ $pago->folio }}</td>
                        <td>{{ $pago->fecha_pago?->format('d/m/Y') }}</td>
                        <td>{{ $pago->boleta?->cliente?->nombre }}</td>
                        <td>{{ $pago->boleta?->folio }}</td>
                        <td>{{ $pago->referencia ?: '—' }}</td>
                        <td>{{ $pago->usuario?->name }}</td>
                        <td class="monto">Q{{ number_format((float) $pago->monto, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6">Subtotal {{ $metodo }}</td>
                    <td class="monto">Q{{ number_format($delMetodo->sum(fn ($p) => (float) $p->monto), 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @empty
        <p class="vacio">No se registraron cobros en este rango de fechas.</p>
    @endforelse

    @if ($pagos->isNotEmpty())
        <div class="resumen">
            <strong>Total cobrado: Q{{ number_format($total, 2) }}</strong>
            &nbsp;·&nbsp; {{ $pagos->count() }} recibos
            &nbsp;·&nbsp; No incluye pagos revertidos.
        </div>
    @endif
</x-reportes.layout>
