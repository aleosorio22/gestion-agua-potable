<x-instalador.layout :paso="2" titulo="Requisitos del servidor"
    descripcion="Revisamos que el servidor tenga todo lo necesario. Si algo falta, quien administra el servidor debe instalarlo.">

    @foreach (collect($requisitos)->groupBy('grupo') as $grupo => $delGrupo)
        <div class="grupo-requisitos">
            <h3>{{ $grupo }}</h3>
            <ul class="revision">
                @foreach ($delGrupo as $requisito)
                    <li class="{{ $requisito['cumple'] ? '' : 'falla' }}">
                        <span class="marca {{ $requisito['cumple'] ? 'si' : 'no' }}" aria-hidden="true">
                            {{ $requisito['cumple'] ? '✓' : '✕' }}
                        </span>
                        <span>
                            <span class="que">{{ $requisito['etiqueta'] }}</span><br>
                            <span class="detalle">{{ $requisito['detalle'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach

    @unless ($cumple)
        <div class="aviso error">
            Falta resolver lo marcado en rojo. Corríjalo en el servidor y vuelva a revisar
            &mdash; no hace falta empezar de nuevo.
        </div>
    @endunless

    <div class="acciones">
        <a class="boton discreto atras" href="{{ route('instalador.bienvenida') }}">Atrás</a>

        @if ($cumple)
            <a class="boton" href="{{ route('instalador.base-de-datos') }}">Continuar</a>
        @else
            <a class="boton" href="{{ route('instalador.requisitos') }}">Volver a revisar</a>
        @endif
    </div>
</x-instalador.layout>
