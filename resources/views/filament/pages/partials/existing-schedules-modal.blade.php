<div class="space-y-4">
    <!-- Course Summary -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $course->title }}</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-600 dark:text-gray-400">Category:</span>
                <span class="ml-2 text-gray-900 dark:text-gray-100">{{ $course->category ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-600 dark:text-gray-400">Duration:</span>
                <span class="ml-2 text-gray-900 dark:text-gray-100">{{ $course->no_of_days ?? 'N/A' }} days</span>
            </div>
        </div>
    </div>

    <!-- Schedules Table -->
    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                Existing Schedules ({{ $schedules->count() }} total)
            </h4>
        </div>
        
        <div class="max-h-96 overflow-auto">
            @if($schedules->count() > 0)
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                        <tr class="text-left">
                            <th class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">Location</th>
                            <th class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">Start Date</th>
                            <th class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">End Date</th>
                            <th class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">Start Timestamp</th>
                            <th class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">Pricing</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($schedules as $schedule)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full mr-2"></div>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $schedule->location }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $schedule->start ? \Carbon\Carbon::parse($schedule->start)->format('M d, Y') : 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $schedule->end ? \Carbon\Carbon::parse($schedule->end)->format('M d, Y') : 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $schedule->start_data ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="space-y-1">
                                        @if($schedule->course_fee_usd)
                                            <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                ${{ number_format($schedule->course_fee_usd) }} USD
                                            </div>
                                        @endif
                                        @if($schedule->course_fee_ksh)
                                            <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ number_format($schedule->course_fee_ksh) }} KES
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-4 py-8 text-center">
                    <div class="mx-auto w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">No schedules found</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">This course doesn't have any schedules yet. Use the bulk action to generate schedules.</p>
                </div>
            @endif
        </div>
    </div>
</div>

