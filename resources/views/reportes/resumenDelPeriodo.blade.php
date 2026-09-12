<x-reportes.layout titulo="Resumen del período" :subtitulo="$subtitulo" :fecha="$fecha">
    @if ($periodo)
        <table>
            <thead>
                <tr>
                    <th style="width: 60%">Concepto</th>
                    <th class="monto">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($resumen as $concepto => $valor)
                    <tr>
                        <td>{{ $concepto }}</td>
                        <td class="monto">{{ $valor }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="resumen">
            <strong>Período del {{ $periodo->fecha_inicio->format('d/m/Y') }}
            al {{ $periodo->fecha_fin->format('d/m/Y') }}</strong>
            &nbsp;·&nbsp;
            {{ $periodo->esta_cerrado ? 'Cerrado el '.$periodo->cerrado_en->format('d/m/Y') : 'Abierto' }}
        </div>

        <div class="resumen" style="margin-top: 30px">
            <p>Revisado por: ______________________________</p>
            <p style="margin-top: 22px">Aprobado por: ______________________________</p>
        </div>
    @else
        <p class="vacio">Seleccione un período para generar el resumen.</p>
    @endif
</x-reportes.layout>
