<div>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('csv-file-input');
        const fileInfo = document.getElementById('file-info');
        
        if (!fileInput) return;

        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                fileInfo.textContent = `Selected: ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
                fileInfo.classList.remove('hidden');
            } else {
                fileInfo.classList.add('hidden');
            }
        });
    });
    </script>
</div>