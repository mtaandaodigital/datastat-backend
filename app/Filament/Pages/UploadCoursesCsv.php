<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class UploadCoursesCsv extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Upload Courses (CSV)';
    protected static ?string $slug = 'upload-courses-csv';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationGroup = 'Data Management';

    protected static string $view = 'filament.pages.upload-courses-csv';
}