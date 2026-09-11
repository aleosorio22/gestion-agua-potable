<x-filament-panels::page>
    <div class="grid grid-cols-2 gap-6">

        @foreach ($this->getReportes() as $clave => $reporte)
            <div
                x-data="{ abierto: false }"
                @click.outside="abierto = false"
                class="relative"
            >

                {{-- Rectángulo del reporte --}}
                <button
                    type="button"
                    @click="abierto = ! abierto"
                    class="flex w-full items-center gap-4 rounded-xl
                           border-2 border-yellow-400
                           bg-black p-4 text-left
                           shadow-sm transition
                           hover:bg-gray-900 hover:shadow-md
                           focus:outline-none focus:ring-2 focus:ring-yellow-400"
                >

                    {{-- Icono --}}
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center
                               rounded-lg bg-yellow-400 text-black"
                    >
                        <x-filament::icon
                            :icon="$reporte['icono']"
                            class="h-6 w-6"
                        />
                    </span>

                    {{-- Información --}}
                    <span class="flex-1">

                        <span class="block text-sm font-semibold text-white">
                            {{ $reporte['titulo'] }}
                        </span>

                        <span class="mt-1 block text-xs text-gray-300">
                            {{ $reporte['descripcion'] }}
                        </span>

                    </span>

                    {{-- Flecha --}}
                    <x-filament::icon
                        icon="heroicon-o-chevron-down"
                        class="h-4 w-4 shrink-0 text-yellow-400 transition-transform"
                        x-bind:class="{ 'rotate-180': abierto }"
                    />

                </button>

                {{-- Dropdown --}}
                <div
                    x-show="abierto"
                    x-transition
                    x-cloak
                    class="absolute z-10 mt-2 w-full overflow-hidden
                           rounded-xl border-2 border-yellow-400
                           bg-black shadow-lg"
                >

                    {{-- PDF --}}
                    <button
                        type="button"
                        wire:click="generarPdf('{{ $clave }}')"
                        @click="abierto = false"
                        class="flex w-full items-center gap-2
                               px-4 py-3 text-sm text-yellow-400
                               transition hover:bg-gray-900"
                    >
                        <x-filament::icon
                            icon="heroicon-o-document-text"
                            class="h-5 w-5 text-yellow-400"
                        />

                        <span>Descargar PDF</span>
                    </button>

                    {{-- Separador --}}
                    <div class="border-t border-yellow-400/40"></div>

                    {{-- Excel --}}
                    <button
                        type="button"
                        wire:click="generarExcel('{{ $clave }}')"
                        @click="abierto = false"
                        class="flex w-full items-center gap-2
                               px-4 py-3 text-sm text-yellow-400
                               transition hover:bg-gray-900"
                    >
                        <x-filament::icon
                            icon="heroicon-o-table-cells"
                            class="h-5 w-5 text-yellow-400"
                        />

                        <span>Descargar Excel</span>
                    </button>

                </div>

            </div>
        @endforeach

    </div>
</x-filament-panels::page>
