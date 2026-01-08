<x-filament::page>
    <div class="space-y-6">
        <!-- Category Filter -->
        <form wire:submit.prevent="noop">
            <x-filament::section>
                <x-slot name="heading">Filter Courses</x-slot>
                {{ $this->form }}
            </x-filament::section>
        </form>

        <!-- Courses Table -->
        {{ $this->table }}

        <!-- Towns Configuration -->
        <form wire:submit.prevent="noop">
            <x-filament::section>
                <x-slot name="heading">Towns & Pricing Configuration</x-slot>
                {{ $this->form->getComponent('towns') }}
            </x-filament::section>
        </form>

        <div>
            <x-filament::button wire:click="noop" icon="heroicon-o-information-circle">
                Tip: Select courses with the checkboxes, then use the bulk action above the table to generate schedules.
            </x-filament::button>
        </div>
    </div>
</x-filament::page>

