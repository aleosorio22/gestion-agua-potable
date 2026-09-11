<x-filament-panels::page>
    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.5rem;">

        @foreach ($this->getReportes() as $clave => $reporte)
            <div
                x-data="{ abierto: false }"
                @click.outside="abierto = false"
                class="relative"
            >

                {{-- Rectángulo clickeable --}}
                <button
                    type="button"
                    @click="abierto = ! abierto"
                    class="w-full flex items-center gap-4 rounded-xl border-2 border-yellow-400
                           bg-black p-4 text-left shadow-sm
                           hover:bg-gray-900 hover:shadow-md transition
                           focus:outline-none focus:ring-2 focus:ring-yellow-400"
                >

                    {{-- Icono --}}
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg
                               bg-yellow-400 text-black"
                    >
                        <x-filament::icon
                            :icon="$reporte['icono']"
                            class="h-6 w-6"
                        />
                    </span>

                    {{-- Información del reporte --}}
                    <span class="flex-1">
                        <span class="block text-sm font-semibold text-white">
                            {{ $reporte['titulo'] }}
                        </span>

                        <span class="block text-xs text-gray-300">
                            {{ $reporte['descripcion'] }}
                        </span>
                    </span>

                    {{-- Flecha --}}
                    <x-filament::icon
                        icon="heroicon-o-chevron-down"
                        x-bind:class="abierto ? 'rotate-180' : ''"
                        class="h-4 w-4 text-yellow-400 transition-transform shrink-0"
                    />

                </button>

                {{-- Dropdown: PDF / Excel --}}
                <div
                    x-show="abierto"
                    x-transition
                    x-cloak
                    class="absolute z-10 mt-1 w-full overflow-hidden
                           rounded-lg bg-black border-2 border-yellow-400 shadow-lg"
                >

                    {{-- PDF --}}
                    <button
                        type="button"
                        wire:click="generarPdf('{{ $clave }}')"
                        @click="abierto = false"
                        class="flex w-full items-center gap-2 px-4 py-2.5
                               text-sm text-yellow-400
                               hover:bg-gray-900 transition"
                    >
                        <x-filament::icon
                            icon="heroicon-o-document-text"
                            class="h-4 w-4 text-yellow-400"
                        />

                        Descargar PDF
                    </button>

                    {{-- Separador --}}
                    <div class="border-t border-yellow-400/40"></div>

                    {{-- Excel --}}
                    <button
                        type="button"
                        wire:click="generarExcel('{{ $clave }}')"
                        @click="abierto = false"
                        class="flex w-full items-center gap-2 px-4 py-2.5
                               text-sm text-yellow-400
                               hover:bg-gray-900 transition"
                    >
                        <x-filament::icon
                            icon="heroicon-o-table-cells"
                            class="h-4 w-4 text-yellow-400"
                        />

                        Descargar Excel
                    </button>

                </div>

            </div>
        @endforeach

    </div>
</x-filament-panels::page>
