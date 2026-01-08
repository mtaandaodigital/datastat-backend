<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['submitted_time'] = now();
        $data['schedule_status'] = $data['schedule_status'] ?? true;

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Course created successfully!';
    }
}