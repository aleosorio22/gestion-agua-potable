@extends('layouts.portal')

@section('title', ($entidad['nombre'] ?? 'Oficina de Agua Potable') . ' — Información')

@section('content')

    <section class="mb-12">
        <h1 class="text-3xl font-bold mb-2">{{ $entidad['nombre'] }}</h1>
        <p class="text-slate-500">
            @if($entidad['municipio'] || $entidad['departamento'])
                {{ $entidad['municipio'] }}{{ $entidad['municipio'] && $entidad['departamento'] ? ', ' : '' }}{{ $entidad['departamento'] }}
            @endif
        </p>
        <div class="mt-4 flex flex-wrap gap-4 text-sm text-slate-600">
            @if($entidad['direccion'])
                <span>📍 {{ $entidad['direccion'] }}</span>
            @endif
            @if($entidad['telefono'])
                <span>📞 {{ $entidad['telefono'] }}</span>
            @endif
        </div>
    </section>

    <section class="mb-12">
        <h2 class="text-xl font-semibold mb-4">Tarifas vigentes</h2>

        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-left">
                    <tr>
                        <th class="px-4 py-3">Paja</th>
                        <th class="px-4 py-3">Consumo incluido</th>
                        <th class="px-4 py-3">Cuota base</th>
                        <th class="px-4 py-3">Excedente por m³</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tarifas as $t)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">{{ $t['paja'] }}</td>
                            <td class="px-4 py-3">{{ $t['equivalencia_m3'] }} m³</td>
                            <td class="px-4 py-3">
                                {{ $t['monto_base'] !== null ? 'Q'.number_format($t['monto_base'], 2) : 'Sin definir' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $t['precio_m3_excedente'] !== null ? 'Q'.number_format($t['precio_m3_excedente'], 4) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-400">
                                Aún no hay tarifas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2 class="text-xl font-semibold mb-4">Sectores cubiertos</h2>
        <div class="flex flex-wrap gap-2">
            @forelse($sectores as $sector)
                <span class="bg-blue-50 text-blue-700 text-sm px-3 py-1 rounded-full">{{ $sector }}</span>
            @empty
                <p class="text-slate-400 text-sm">Aún no hay sectores registrados.</p>
            @endforelse
        </div>
    </section>

@endsection