<x-filament::page>
    <div class="space-y-6">
        <form wire:submit.prevent="submit">
            {{ $this->form }}

            <div class="mt-4">
                <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray">
                    Generate & Download
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament::page>

