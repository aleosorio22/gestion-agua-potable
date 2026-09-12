<x-filament-panels::page>
    {{-- Los filtros arriba: cada tarjeta dice cuáles usa, para que nadie
         elija un rango de fechas en un reporte que lo ignora. --}}
    {{ $this->filtrosForm }}

    @foreach ($this->getReportesPorGrupo() as $grupo => $delGrupo)
        <section class="mt-6">
            <h2 class="mb-3 text-base font-semibold text-gray-950 dark:text-white">
                {{ $grupo }}
            </h2>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($delGrupo as $clave => $reporte)
                    <div @class([
                        'flex flex-col rounded-xl border bg-white p-4 shadow-sm',
                        'border-gray-200 dark:border-white/10 dark:bg-gray-900',
                    ])>
                        <div class="flex items-start gap-3">
                            <span @class([
                                'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
                                'bg-primary-50 text-primary-600',
                                'dark:bg-primary-500/10 dark:text-primary-400',
                            ])>
                                <x-filament::icon :icon="$reporte['icono']" class="h-5 w-5" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $reporte['titulo'] }}
                                </h3>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $reporte['descripcion'] }}
                                </p>
                            </div>
                        </div>

                        @if ($reporte['filtros'])
                            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                                Usa:
                                {{ collect($reporte['filtros'])
                                    ->map(fn (string $filtro): string => match ($filtro) {
                                        'periodo' => 'período',
                                        'fechas' => 'rango de fechas',
                                        'sector' => 'sector',
                                        default => $filtro,
                                    })
                                    ->implode(', ') }}
                            </p>
                        @endif

                        <div class="mt-4 flex gap-2 border-t border-gray-100 pt-3 dark:border-white/5">
                            <x-filament::button
                                wire:click="generarPdf('{{ $clave }}')"
                                wire:loading.attr="disabled"
                                wire:target="generarPdf('{{ $clave }}')"
                                size="sm"
                                color="danger"
                                icon="heroicon-o-document-arrow-down"
                                class="flex-1"
                            >
                                PDF
                            </x-filament::button>

                            <x-filament::button
                                wire:click="generarExcel('{{ $clave }}')"
                                wire:loading.attr="disabled"
                                wire:target="generarExcel('{{ $clave }}')"
                                size="sm"
                                color="success"
                                icon="heroicon-o-table-cells"
                                class="flex-1"
                            >
                                Excel
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</x-filament-panels::page>
