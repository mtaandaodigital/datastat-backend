<x-filament::page>
    <div class="space-y-6" x-data>
        <form id="csv-upload-form" enctype="multipart/form-data" method="POST" action="{{ route('admin.upload-csv') }}">
        @csrf
        
        <x-filament::section>
            <x-slot name="heading">Upload CSV</x-slot>
            
            <div class="space-y-6">
                <!-- CSV File Upload -->
                <div class="space-y-2">
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                        <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                            CSV File *
                        </span>
                    </label>
                    
                    <div class="fi-fo-file-upload flex flex-col gap-4">
                        <input 
                            type="file" 
                            id="csv-file-input"
                            name="csv_file"
                            accept=".csv,text/csv,application/csv"
                            class="fi-fo-text-input block w-full border-gray-300 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-70 rounded-lg"
                        >
                        
                        <div class="text-sm text-gray-500">
                            Upload a CSV file (max 10MB)
                        </div>
                        
                        <div id="file-info" class="hidden text-sm text-green-600">
                            <!-- File info will be displayed here -->
                        </div>
                    </div>
                </div>

                <!-- Has Header Toggle -->
                <div class="flex items-center gap-x-3">
                    <input 
                        type="checkbox" 
                        id="has_header" 
                        name="data[has_header]" 
                        value="1"
                        checked
                        class="fi-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                    >
                    <label for="has_header" class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                        File has header row
                    </label>
                </div>

                <!-- Schedule Generation Settings -->
                <div class="space-y-4 border-t pt-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Schedule Generation Settings</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Configure how schedules are generated for uploaded courses. Location will be taken from CSV (defaults to Nairobi if blank).</p>
                    
                    <div class="space-y-2">
                        <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                Schedule Generation End Date
                            </span>
                        </label>
                        
                        <input 
                            type="date" 
                            id="schedule_end_date" 
                            name="data[schedule_end_date]" 
                            value="2027-01-01"
                            class="fi-fo-text-input block w-full border-gray-300 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-70 rounded-lg"
                        >
                        
                        <div class="text-sm text-gray-500">
                            Generate consecutive schedules up to this date (leave empty for single schedule only)
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
        
        <div class="mt-6">
            <button 
                type="submit"
                id="upload-btn"
                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-blue-600 text-white hover:bg-blue-500 focus-visible:ring-blue-500/50"
            >
                <svg class="fi-btn-icon transition duration-75 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z" clip-rule="evenodd"/>
                </svg>
                <span id="btn-text">Upload & Import</span>
                <svg id="loading-spinner" class="animate-spin h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </div>
        
        <div id="upload-message" class="mt-4 hidden"></div>
    </form>

    @if (session('success'))
        <div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            const form = document.getElementById('csv-upload-form');
            if (!form) return;

            const btn = document.getElementById('upload-btn');
            const btnText = document.getElementById('btn-text');
            const spinner = document.getElementById('loading-spinner');
            const messageDiv = document.getElementById('upload-message');
            const fileInput = document.getElementById('csv-file-input');
            const fileInfo = document.getElementById('file-info');

            if (fileInput && fileInfo) {
                fileInput.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        const file = e.target.files[0];
                        fileInfo.textContent = `Selected: ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
                        fileInfo.classList.remove('hidden');
                    } else {
                        fileInfo.classList.add('hidden');
                    }
                });
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const csvInput = document.getElementById('csv-file-input');
                const hasHeaderToggle = document.querySelector('input[name="data[has_header]"]');
                const dateInput = document.querySelector('input[name="data[schedule_end_date]"]');

                if (!csvInput.files.length) {
                    showMessage('Please select a CSV file', 'error');
                    return;
                }

                btn.disabled = true;
                btn.classList.add('opacity-50');
                btnText.textContent = 'Processing...';
                spinner.classList.remove('hidden');

                const formData = new FormData();
                formData.append('csv_file', csvInput.files[0]);
                formData.append('has_header', hasHeaderToggle?.checked ? '1' : '0');
                formData.append('schedule_end_date', dateInput?.value || '');
                formData.append('_token', document.querySelector('input[name="_token"]').value);

                fetch('{{ route("admin.upload-csv") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    const details = data.details ? `\nTotal: ${data.details.total}, Success: ${data.details.success}, Failed: ${data.details.failed}` : '';
                    const schedTotal = data.details?.total_schedules_created != null ? `\nSchedules Created: ${data.details.total_schedules_created}` : '';
                    const perCourse = Array.isArray(data.details?.per_course) && data.details.per_course.length
                      ? `\nPer Course:\n- ` + data.details.per_course.map(c => `${c.title}: ${c.schedules}`).join('\n- ')
                      : '';
                    const errs = data.details?.errors?.length ? `\nErrors: ${data.details.errors.join(' | ')}` : '';
                    const msg = `${data.message}${details}${schedTotal}${perCourse}${errs}`.trim();

                    if (data.success) {
                        showMessage(msg, 'success');
                        form.reset();
                        if (fileInfo) fileInfo.classList.add('hidden');
                    } else {
                        showMessage(msg, 'error');
                    }
                })
                .catch(() => showMessage('An error occurred during upload', 'error'))
                .finally(() => {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50');
                    btnText.textContent = 'Upload & Import';
                    spinner.classList.add('hidden');
                });
            });

            function showMessage(message, type) {
                messageDiv.innerHTML = `
                    <div class="rounded-md p-4 ${type === 'success' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'} border">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                ${type === 'success' ? 
                                    '<svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>' :
                                    '<svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>'
                                }
                            </div>
                            <div class="ml-3">
                                <p class="text-sm ${type === 'success' ? 'text-green-800' : 'text-red-800'}">${message}</p>
                            </div>
                        </div>
                    </div>
                `;
                messageDiv.classList.remove('hidden');
            }
        });
    </script>
    @endpush
    </div>
</x-filament::page>