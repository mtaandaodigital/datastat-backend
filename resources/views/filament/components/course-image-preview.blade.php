@php
    $record = $getRecord();
    $imageUrl = $record ? $record->getImageUrlAttribute() : null;
@endphp

@if($imageUrl)
    <div class="space-y-2">
        <div class="rounded-lg overflow-hidden border border-gray-300 dark:border-gray-700">
            <img 
                src="{{ $imageUrl }}" 
                alt="{{ $record->title ?? 'Course image' }}" 
                class="w-full h-auto object-cover"
            />
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Current image: {{ $record->image_path }}
        </div>
    </div>
@else
    <div class="text-sm text-gray-500 dark:text-gray-400">
        No image has been uploaded yet.
    </div>
@endif