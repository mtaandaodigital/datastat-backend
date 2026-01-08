<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Progress Section -->
        @if($showProgress && $currentImport)
            <livewire:csv-import-progress :import="$currentImport" />
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Import Actions</h3>
                        <p class="text-sm text-gray-600">Manage your current import process</p>
                    </div>
                    <div class="flex space-x-3">
                        <button wire:click="viewImport" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            View Details
                        </button>
                        <button wire:click="resetImport" 
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            New Import
                        </button>
                    </div>
                </div>
            </div>
        @else
            <!-- Import Form -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">CSV Import</h2>
                <p class="text-sm text-gray-600 mb-6">
                    Upload CSV files to bulk import data into the system. Make sure your CSV file follows the correct format.
                </p>
                
                <form wire:submit="import">
                    {{ $this->form }}
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        {{ $this->getFormActions() }}
                    </div>
                </form>
            </div>
            
            <!-- Import Tips -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Import Tips</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Ensure your CSV file has proper column headers</li>
                                <li>Date fields should be in YYYY-MM-DD format</li>
                                <li>Email addresses must be unique for user imports</li>
                                <li>Large files will be processed in batches</li>
                                <li>You can monitor import progress with the real-time progress bar</li>
                                <li>The counter will show "1, 2, 3..." as each course is uploaded</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Enhanced Progress Tracking -->
    <script>
        let progressInterval = null;

        // Fix Livewire upload issues
        document.addEventListener('DOMContentLoaded', () => {
            // Override Livewire upload error handler
            window.addEventListener('livewire:upload-error', (e) => {
                console.warn('Upload error caught:', e.detail);
                // Prevent the error from breaking the page
                e.preventDefault();
            });

            // Add upload progress handler
            window.addEventListener('livewire:upload-progress', (e) => {
                console.log('Upload progress:', e.detail.progress + '%');
            });
        });

        document.addEventListener('livewire:init', () => {
            // Listen for import completion events
            Livewire.on('import-completed', (event) => {
                console.log('Import completed successfully:', event);
                if (progressInterval) {
                    clearInterval(progressInterval);
                    progressInterval = null;
                }
            });

            Livewire.on('import-failed', (event) => {
                console.log('Import failed:', event);
                if (progressInterval) {
                    clearInterval(progressInterval);
                    progressInterval = null;
                }
            });

            // Auto-refresh progress during processing
            const startAutoRefresh = () => {
                if (progressInterval) clearInterval(progressInterval);
                
                progressInterval = setInterval(() => {
                    if (document.querySelector('[wire\\:poll]')) {
                        Livewire.dispatch('refresh-progress');
                    } else {
                        clearInterval(progressInterval);
                        progressInterval = null;
                    }
                }, 2000); // Refresh every 2 seconds
            };

            // Start auto-refresh if there's an active import
            if (document.querySelector('.animate-spin')) {
                startAutoRefresh();
            }
        });
    </script>
</x-filament-panels::page>