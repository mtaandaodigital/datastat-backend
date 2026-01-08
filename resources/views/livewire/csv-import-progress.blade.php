<div class="space-y-4" @if($showProgress && in_array($progressData['status'], ['pending', 'processing'])) wire:poll.2s @endif>
    @if($showProgress)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    Import Progress
                </h3>
                <div class="flex items-center space-x-2">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                    <span class="text-sm text-gray-600 capitalize">{{ $progressData['status'] }}</span>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="mb-6">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Progress: {{ $progressData['processed_rows'] }} / {{ $progressData['total_rows'] }} rows</span>
                    <span>{{ number_format($progressData['progress_percentage'], 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-300 ease-out" 
                         style="width: {{ $progressData['progress_percentage'] }}%"></div>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($progressData['total_rows']) }}</div>
                    <div class="text-sm text-gray-600">Total Rows</div>
                </div>
                <div class="text-center p-3 bg-blue-50 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ number_format($progressData['processed_rows']) }}</div>
                    <div class="text-sm text-gray-600">Processed</div>
                </div>
                <div class="text-center p-3 bg-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ number_format($progressData['successful_rows']) }}</div>
                    <div class="text-sm text-gray-600">Successful</div>
                </div>
                <div class="text-center p-3 bg-red-50 rounded-lg">
                    <div class="text-2xl font-bold text-red-600">{{ number_format($progressData['failed_rows']) }}</div>
                    <div class="text-sm text-gray-600">Failed</div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="flex justify-between items-center text-sm text-gray-600">
                <div>
                    @if($progressData['duration'])
                        <span>Duration: {{ $progressData['duration'] }}</span>
                    @endif
                </div>
                <div>
                    @if($progressData['processed_rows'] > 0)
                        Success Rate: {{ number_format($progressData['success_rate'], 1) }}%
                    @endif
                </div>
            </div>

            <!-- Live Counter Display -->
            @if($progressData['status'] === 'processing' && $progressData['processed_rows'] > 0)
                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                        <span class="text-sm text-blue-700 font-medium">
                            Processing... {{ $progressData['successful_rows'] }} courses uploaded successfully
                        </span>
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if($progressData['is_completed'])
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center space-x-3">
                @if($progressData['status'] === 'completed')
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-green-900">Import Completed Successfully!</h3>
                        <p class="text-green-700">
                            {{ number_format($progressData['successful_rows']) }} courses and schedules were imported successfully.
                            @if($progressData['failed_rows'] > 0)
                                {{ number_format($progressData['failed_rows']) }} rows failed to import.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-red-900">Import Failed</h3>
                        <p class="text-red-700">The import process encountered errors. Please check the error details below.</p>
                    </div>
                @endif
            </div>

            <!-- Final Statistics -->
            <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <div class="text-xl font-bold text-gray-900">{{ number_format($progressData['total_rows']) }}</div>
                    <div class="text-sm text-gray-600">Total Rows</div>
                </div>
                <div class="text-center p-3 bg-blue-50 rounded-lg">
                    <div class="text-xl font-bold text-blue-600">{{ number_format($progressData['processed_rows']) }}</div>
                    <div class="text-sm text-gray-600">Processed</div>
                </div>
                <div class="text-center p-3 bg-green-50 rounded-lg">
                    <div class="text-xl font-bold text-green-600">{{ number_format($progressData['successful_rows']) }}</div>
                    <div class="text-sm text-gray-600">Successful</div>
                </div>
                <div class="text-center p-3 bg-red-50 rounded-lg">
                    <div class="text-xl font-bold text-red-600">{{ number_format($progressData['failed_rows']) }}</div>
                    <div class="text-sm text-gray-600">Failed</div>
                </div>
            </div>

            @if($progressData['duration'])
                <div class="mt-4 text-center text-sm text-gray-600">
                    Total Duration: {{ $progressData['duration'] }}
                </div>
            @endif
        </div>
    @endif
</div>
