<x-reportes.layout titulo="Anulaciones y reversos" :subtitulo="$subtitulo" :fecha="$fecha">
    <div class="grupo">Boletas anuladas</div>

    <table>
        <thead>
            <tr>
                <th>Folio</th>
                <th>Cliente</th>
                <th>Período</th>
                <th class="monto">Monto</th>
                <th>Anulada</th>
                <th>Por</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($boletas as $boleta)
                <tr>
                    <td>{{ $boleta->folio }}</td>
                    <td>{{ $boleta->cliente?->nombre }}</td>
                    <td>{{ $boleta->periodo?->etiqueta }}</td>
                    <td class="monto">Q{{ number_format((float) $boleta->monto, 2) }}</td>
                    <td>{{ $boleta->anulada_en?->format('d/m/Y H:i') }}</td>
                    <td>{{ $boleta->anuladaPor?->name ?: '—' }}</td>
                    <td>{{ $boleta->motivo_anulacion }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="vacio">No se anularon boletas en este rango.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="grupo">Pagos revertidos</div>

    <table>
        <thead>
            <tr>
                <th>Recibo</th>
                <th>Cliente</th>
                <th>Boleta</th>
                <th class="monto">Monto</th>
                <th>Revertido</th>
                <th>Por</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pagos as $pago)
                <tr>
                    <td>{{ $pago->folio }}</td>
                    <td>{{ $pago->boleta?->cliente?->nombre }}</td>
                    <td>{{ $pago->boleta?->folio }}</td>
                    <td class="monto">Q{{ number_format((float) $pago->monto, 2) }}</td>
                    <td>{{ $pago->revertido_en?->format('d/m/Y H:i') }}</td>
                    <td>{{ $pago->revertidoPor?->name ?: '—' }}</td>
                    <td>{{ $pago->motivo_reverso }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="vacio">No se revirtieron pagos en este rango.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="resumen">
        Ni las boletas ni los pagos se borran del sistema: quedan registrados junto al hecho que
        los deja sin efecto, con su autor y su motivo.
    </div>
</x-reportes.layout>
