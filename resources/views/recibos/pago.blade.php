<x-recibo :titulo="'Recibo de pago '.$pago->folio" :ajustes="$ajustes">
    <p class="titulo">{{ $ajustes->etiqueta('recibo.titulo_pago') }}</p>
    <p class="folio">{{ $pago->folio }}</p>

    <hr class="separador">

    <dl>
        <div class="campo"><dt>Recibí de</dt><dd>{{ $boleta->cliente->nombre }}</dd></div>
        <div class="campo"><dt>Código</dt><dd>{{ $boleta->cliente->codigo }}</dd></div>
        @if ($boleta->lectura?->contador)
            <div class="campo">
                <dt>{{ $ajustes->etiqueta('recibo.contador') }}</dt>
                <dd>{{ $boleta->lectura->contador->codigo }}</dd>
            </div>
        @endif
        <div class="campo"><dt>Fecha</dt><dd>{{ $pago->fecha_pago->format('d/m/Y') }}</dd></div>
    </dl>

    <hr class="separador">

    <dl>
        <div class="campo">
            <dt>Concepto</dt>
            <dd>{{ $ajustes->etiqueta('recibo.servicio') }}</dd>
        </div>
        <div class="campo"><dt>Boleta</dt><dd>{{ $boleta->folio }}</dd></div>
        <div class="campo"><dt>Período</dt><dd>{{ $boleta->periodo->etiqueta_larga }}</dd></div>
        <div class="campo"><dt>Método</dt><dd>{{ $pago->metodoPago->nombre }}</dd></div>
        @if ($pago->referencia)
            <div class="campo"><dt>Referencia</dt><dd>{{ $pago->referencia }}</dd></div>
        @endif
    </dl>

    <hr class="separador">

    <p class="linea">
        <span>Total de la boleta</span>
        <span class="monto">Q.{{ number_format((float) $boleta->monto, 2) }}</span>
    </p>

    <p class="destacado">
        {{ $ajustes->etiqueta('recibo.recibi') }} Q.{{ number_format((float) $pago->monto, 2) }}
    </p>

    @if ($saldo > 0)
        {{-- Un abono parcial tiene que decirlo en el papel: si no, el vecino se
             va creyendo que quedó al día. --}}
        <p class="centrado">
            {{ $ajustes->etiqueta('recibo.saldo') }}: Q.{{ number_format($saldo, 2) }}
        </p>
    @else
        <p class="recuadro">{{ $ajustes->etiqueta('recibo.saldado') }}</p>
    @endif

    <hr class="separador">

    <div class="pie">
        <p>Atendido por: {{ $pago->usuario->name }}</p>
        <p>Impreso {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="firma">
        <div class="linea-firma"></div>
        <p>Firma y sello de la oficina</p>
    </div>
</x-recibo>
