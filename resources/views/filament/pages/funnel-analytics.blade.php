<x-filament::page>
    <div class="space-y-6">
        <form wire:submit.prevent="noop">
            {{ $this->form }}
        </form>

        @php($stats = $this->getStats())
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <x-filament::section>
                <div class="text-sm text-gray-600">Total Leads</div>
                <div class="text-2xl font-bold">{{ $stats['total'] ?? 0 }}</div>
            </x-filament::section>
            <x-filament::section>
                <div class="text-sm text-gray-600">New</div>
                <div class="text-2xl font-bold">{{ $stats['new'] ?? 0 }}</div>
            </x-filament::section>
            <x-filament::section>
                <div class="text-sm text-gray-600">Contacted</div>
                <div class="text-2xl font-bold">{{ $stats['contacted'] ?? 0 }}</div>
            </x-filament::section>
            <x-filament::section>
                <div class="text-sm text-gray-600">Converted</div>
                <div class="text-2xl font-bold">{{ $stats['converted'] ?? 0 }}</div>
            </x-filament::section>
            <x-filament::section>
                <div class="text-sm text-gray-600">Conversion Rate</div>
                <div class="text-2xl font-bold">{{ $stats['rate'] ?? 0 }}%</div>
            </x-filament::section>
        </div>

        <div>
            <p class="text-sm text-gray-600">Tip: Adjust filters above to update funnel stats.</p>
        </div>
    </div>
</x-filament::page>

