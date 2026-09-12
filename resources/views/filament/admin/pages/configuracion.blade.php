<x-filament-panels::page>
    <form wire:submit="guardar">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            @foreach ($this->getFormActions() as $accion)
                {{ $accion }}
            @endforeach
        </div>
    </form>
</x-filament-panels::page>
