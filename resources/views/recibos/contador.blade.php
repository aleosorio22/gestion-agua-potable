<x-recibo :titulo="'Recibo — contador '.$contador->codigo" :ajustes="$ajustes">
    <dl>
        <div class="campo">
            <dt>Documento</dt>
            <dd>{{ $ajustes->etiqueta('recibo.titulo_cobro') }}</dd>
        </div>
    </dl>

    <hr class="separador">

    <dl>
        <div class="campo">
            <dt>{{ $ajustes->etiqueta('recibo.contribuyente') }}</dt>
            <dd>{{ $contador->cliente->nombre }}</dd>
        </div>
        <div class="campo"><dt>Código</dt><dd>{{ $contador->cliente->codigo }}</dd></div>
        @if ($contador->cliente->nit)
            <div class="campo"><dt>NIT</dt><dd>{{ $contador->cliente->nit }}</dd></div>
        @endif
        @if ($contador->cliente->dpi)
            <div class="campo"><dt>DPI</dt><dd>{{ $contador->cliente->dpi }}</dd></div>
        @endif
        <div class="campo">
            <dt>Dirección</dt>
            <dd>{{ $contador->predio?->direccion_completa ?: 'Sin dirección registrada' }}</dd>
        </div>
        @if ($contador->predio?->sector)
            <div class="campo"><dt>Sector</dt><dd>{{ $contador->predio->sector->nombre }}</dd></div>
        @endif
    </dl>

    <hr class="separador">

    <dl>
        <div class="campo"><dt>Generado</dt><dd>{{ now()->format('d/m/Y') }}</dd></div>
        @if ($vence)
            <div class="campo"><dt>Vencimiento</dt><dd>{{ $vence->format('d/m/Y') }}</dd></div>
        @endif
    </dl>

    <hr class="separador">

    @if ($boletas->isEmpty())
        <p class="sin-deuda">Este servicio no tiene saldo pendiente.</p>
    @else
        <p class="servicio linea">
            <span>
                {{ $ajustes->etiqueta('recibo.servicio') }} —
                {{ $ajustes->etiqueta('recibo.contador') }}: {{ $contador->codigo }}
            </span>
            <span class="monto">Q.{{ number_format($total, 2) }}</span>
        </p>

        @foreach ($conceptos as $concepto)
            <div class="concepto">
                <div>{{ $concepto['concepto'] }}</div>
                <div class="linea detalle">
                    <span>{{ $concepto['periodo'] }}</span>
                    <span class="monto">Q.{{ number_format($concepto['monto'], 2) }}</span>
                </div>
            </div>
        @endforeach

        @php($ultima = $boletas->last())

        <div class="lecturas">
            <table>
                <caption>{{ $ajustes->etiqueta('recibo.lecturas') }}</caption>
                <tbody>
                    <tr>
                        <td>Anterior</td>
                        <td>{{ number_format((float) $ultima->lectura->lectura_anterior, 0) }}</td>
                        <td class="consumo"></td>
                    </tr>
                    <tr>
                        <td>{{ $ultima->periodo->etiqueta }}</td>
                        <td>{{ number_format((float) $ultima->lectura->lectura_actual, 0) }}</td>
                        <td class="consumo">{{ number_format((float) $ultima->consumo_m3, 0) }} m³</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="destacado">
            {{ $ajustes->etiqueta('recibo.total') }} Q.{{ number_format($total, 2) }}
        </p>

        @if ($boletas->count() > 1)
            <p style="text-align:center">Incluye {{ $boletas->count() }} meses pendientes.</p>
        @endif
    @endif

    <hr class="separador">

    <div class="pie">
        <p>Folios: {{ $boletas->pluck('folio')->implode(', ') ?: '—' }}</p>
        <p>Atendido por: {{ auth()->user()->name }}</p>
        <p>Impreso {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</x-recibo>
