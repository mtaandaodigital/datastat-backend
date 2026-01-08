<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\ReplicateAction::make()
                ->beforeReplicaSaved(function ($replica): void {
                    $replica->title = $replica->title . ' (Copy)';
                    $replica->submitted_time = now();
                }),
            Actions\DeleteAction::make()
                ->modalHeading('Delete course')
                ->modalDescription('This will also delete all related schedules. This action cannot be undone.')
                ->before(function (\App\Models\Course $record): void {
                    $record->schedules()->delete();
                })
                ->visible(fn() => auth()->user()->isSuperAdmin()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Course updated successfully!';
    }
}