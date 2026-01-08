<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header with Month Navigation -->
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">Registration Calendar</h2>
            <div class="flex items-center space-x-4">
                <button wire:click="goToPreviousMonth" class="px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">
                    <x-heroicon-o-chevron-left class="w-5 h-5" />
                </button>
                <button wire:click="toggleDatePicker" class="px-6 py-2 rounded-lg border-2 border-blue-500 bg-blue-50 hover:bg-blue-100 text-lg font-semibold cursor-pointer">
                    {{ $currentMonthLabel }}
                </button>
                <button wire:click="goToNextMonth" class="px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">
                    <x-heroicon-o-chevron-right class="w-5 h-5" />
                </button>
            </div>
        </div>

        <!-- Date Picker Modal -->
        @if($showDatePicker)
            <div class="bg-white rounded-lg shadow-lg p-8 border-2 border-blue-300">
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Select Year</label>
                    <select wire:model.live="selectedYear" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none text-lg font-semibold">
                        @php
                            $currentYear = Carbon\Carbon::createFromFormat('Y-m', $currentMonth)->year;
                            $startYear = $currentYear - 2;
                            $endYear = $currentYear + 5;
                        @endphp
                        @foreach(range($startYear, $endYear) as $year)
                            <option value="{{ $year }}" @if($selectedYear == $year) selected @endif>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Select Month</label>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                        @foreach(range(1, 12) as $monthNum)
                            @php
                                $monthDate = \Carbon\Carbon::createFromDate($selectedYear, $monthNum, 1)->format('Y-m');
                                $isSelected = $monthDate === $currentMonth;
                                $monthKey = $monthNum - 1;
                                $count = isset($monthCounts[$monthKey]) ? $monthCounts[$monthKey] : 0;
                            @endphp
                            <button 
                                wire:click="selectMonth('{{ $monthDate }}')"
                                class="py-4 px-3 text-base font-semibold rounded-lg transition-all duration-200 relative
                                    @if($isSelected)
                                        bg-blue-500 text-white shadow-lg border-2 border-blue-600 scale-105
                                    @else
                                        bg-gray-100 border-2 border-gray-300 hover:bg-blue-50 hover:border-blue-400 text-gray-700
                                    @endif
                                "
                            >
                                <div>{{ \Carbon\Carbon::createFromDate($selectedYear, $monthNum, 1)->format('M') }}</div>
                                <div class="text-xs mt-1 
                                    @if($isSelected)
                                        bg-blue-600 text-white
                                    @else
                                        bg-gray-300 text-gray-800
                                    @endif
                                    rounded px-2 py-1">
                                    {{ $count }}
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <button wire:click="toggleDatePicker" class="mt-6 w-full px-6 py-3 bg-gray-400 hover:bg-gray-500 text-white rounded-lg font-semibold transition-colors">
                    Close
                </button>
            </div>
        @endif

        <!-- Upcoming Registrations -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Upcoming Registrations This Month <span class="bg-blue-500 text-white rounded-full px-3 py-1 text-sm">{{ $upcomingCount }}</span></h3>
            @if(count($upcoming) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($upcoming as $reg)
                        <div class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer" wire:click="openModal({{ $reg['id'] }})">
                            <div class="font-semibold">{{ $reg['name'] }}</div>
                            <div class="text-sm text-gray-600">{{ $reg['course'] }}</div>
                            <div class="text-sm">{{ $reg['start'] }} - {{ $reg['end'] }} | {{ $reg['location'] }}</div>
                            <div class="text-xs text-gray-500">Registered: {{ $reg['registration_date'] }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">No upcoming registrations this month.</p>
            @endif
        </div>

        <!-- Week Filters -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Select Week</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($weeks as $week)
                    <button 
                        wire:click="selectWeek({{ $week['index'] }})"
                        class="px-4 py-2 rounded-lg border-2 transition-colors duration-200 relative
                            @if($selectedWeekIndex === $week['index'])
                                border-blue-500 bg-blue-50
                            @else
                                border-gray-300 bg-white hover:border-blue-300
                            @endif
                        "
                    >
                        <span class="font-medium">{{ $week['label'] }}</span>
                        <span class="ml-2 inline-flex items-center justify-center bg-blue-500 text-white rounded-full w-6 h-6 text-xs font-semibold">
                            {{ $week['count'] }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Selected Week Registrations -->
        @if($selectedWeekIndex !== null)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Registrations for {{ $selectedWeekLabel }}</h3>
                @if(count($registrations) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4">Name</th>
                                    <th class="text-left py-3 px-4">Course</th>
                                    <th class="text-left py-3 px-4">Dates</th>
                                    <th class="text-left py-3 px-4">Location</th>
                                    <th class="text-left py-3 px-4">Payment Status</th>
                                    <th class="text-left py-3 px-4">Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($registrations as $reg)
                                    <tr class="border-b hover:bg-gray-50 cursor-pointer" wire:click="openModal({{ $reg['id'] }})">
                                        <td class="py-3 px-4">
                                            <div class="font-semibold">{{ $reg['name'] }}</div>
                                        </td>
                                        <td class="py-3 px-4">{{ $reg['course'] }}</td>
                                        <td class="py-3 px-4 text-sm">{{ $reg['start']->format('M d') }} - {{ $reg['end']->format('M d') }}</td>
                                        <td class="py-3 px-4 text-sm">{{ $reg['location'] }}</td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 rounded text-xs font-semibold
                                                @if($reg['payment_status'] == 'Paid')
                                                    bg-green-100 text-green-800
                                                @elseif($reg['payment_status'] == 'Pending')
                                                    bg-yellow-100 text-yellow-800
                                                @else
                                                    bg-red-100 text-red-800
                                                @endif
                                            ">
                                                {{ $reg['payment_status'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-sm text-gray-500">{{ $reg['registration_date'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500">No registrations for this week.</p>
                @endif
            </div>
        @else
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                <p class="text-blue-800">Select a week above to view registrations for that week</p>
            </div>
        @endif
    </div>

    <!-- Modal -->
    @if($showModal && $selectedRegistrant)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Registration Details
                                </h3>
                                <div class="mt-4 space-y-3">
                                    <div><strong>Name:</strong> {{ $selectedRegistrant->full_name }}</div>
                                    <div><strong>Course:</strong> {{ $selectedRegistrant->schedule->course?->title ?? $selectedRegistrant->title_course }}</div>
                                    <div><strong>Location:</strong> {{ $selectedRegistrant->schedule->location }}</div>
                                    <div><strong>Dates:</strong> {{ \Carbon\Carbon::parse($selectedRegistrant->schedule->start)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($selectedRegistrant->schedule->end)->format('M d, Y') }}</div>
                                    <div><strong>Organization:</strong> {{ $selectedRegistrant->organization }}</div>
                                    <div><strong>Email:</strong> {{ $selectedRegistrant->official_email ?: $selectedRegistrant->personal_email }}</div>
                                    <div><strong>Phone:</strong> {{ $selectedRegistrant->phone }}</div>
                                    <div><strong>Payment Status:</strong> {{ $selectedRegistrant->payment_status ?? 'Pending' }}</div>
                                    <div><strong>Registration Date:</strong> {{ $selectedRegistrant->registered_time }}</div>
                                    @if($selectedRegistrant->accommodation == 'yes')
                                        <div><strong>Accommodation:</strong> Required</div>
                                    @endif
                                    @if($selectedRegistrant->airport_pickup == 'yes')
                                        <div><strong>Airport Pickup:</strong> Required</div>
                                    @endif
                                    @if($selectedRegistrant->expectations)
                                        <div><strong>Expectations:</strong> {{ $selectedRegistrant->expectations }}</div>
                                    @endif
                                </div>
                                <div class="mt-4">
                                    <label for="customMessage" class="block text-sm font-medium text-gray-700">Custom Message (Optional)</label>
                                    <textarea wire:model="customMessage" id="customMessage" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="sendReminder" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Send Reminder Email
                        </button>
                        <button wire:click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>