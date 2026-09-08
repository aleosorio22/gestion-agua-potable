<x-filament-panels::page>
    @if ($this->aviso)
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $this->aviso }}
            </p>
        </x-filament::section>
    @endif

    @if ($this->getPeriodo())
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            {{-- Avance del recorrido: cuánto falta para cerrar la ruta del mes. --}}
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Período {{ $this->getPeriodo()->etiqueta }}
                </p>
                <p class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $this->avance['leidos'] }} de {{ $this->avance['total'] }}
                    <span class="text-base font-normal text-gray-500 dark:text-gray-400">
                        contadores leídos
                    </span>
                </p>
            </div>

            @if (count($this->periodosDisponibles) > 1)
                <div>
                    <label
                        for="periodo-de-la-ruta"
                        class="block text-sm font-medium text-gray-950 dark:text-white"
                    >
                        Período
                    </label>
                    <select
                        id="periodo-de-la-ruta"
                        wire:model.live="periodoId"
                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-white"
                    >
                        @foreach ($this->periodosDisponibles as $id => $etiqueta)
                            <option value="{{ $id }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <x-filament::tabs>
            <x-filament::tabs.item
                :active="$vista === 'pendientes'"
                wire:click="cambiarVista('pendientes')"
            >
                Pendientes
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$vista === 'leidos'"
                wire:click="cambiarVista('leidos')"
            >
                Leídos
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{ $this->table }}
    @endif
</x-filament-panels::page>
