<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Generate Custom Report</h2>
            <p class="text-gray-600 mb-6">
                Create detailed reports for your business data. Select the report type, date range, and format to generate comprehensive analytics.
            </p>
            
            <form wire:submit.prevent="generateReport">
                {{ $this->form }}
                
                <div class="mt-6 flex justify-end space-x-3">
                    {{ $this->getFormActions() }}
                </div>
            </form>
        </div>
        
        <div class="bg-blue-50 rounded-lg p-6">
            <h3 class="text-md font-semibold text-blue-900 mb-3">Quick Tips</h3>
            <ul class="text-blue-800 space-y-2 text-sm">
                <li>• Reports are generated in real-time and may take a few moments for large datasets</li>
                <li>• CSV format is recommended for Excel compatibility</li>
                <li>• Date ranges can span up to 2 years for optimal performance</li>
                <li>• Generated reports are automatically saved and can be downloaded later</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>