<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        // Public disk used by FileUpload and public assets via /storage symlink
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // Optional: direct public root access (not required by default)
        'public_root' => [
            'driver' => 'local',
            'root' => public_path(),
            'url' => env('APP_URL'),
            'visibility' => 'public',
            'throw' => false,
        ],
        
        // Main site uploads directory
        'main_site_uploads' => [
            'driver' => 'local',
            'root' => env('MAIN_SITE_UPLOADS_PATH', base_path('../website/public/images/courses')),
            'url' => env('MAIN_SITE_UPLOADS_URL', 'https://datastatresearch.org/images/courses'),
            'visibility' => 'public',
            'throw' => false,
        ],

        // Category images directory on main site
        'main_site_category' => [
            'driver' => 'local',
            'root' => '/website/public/images/category',
            'url' => 'https://datastatresearch.org/images/category',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

