<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCourse extends ViewRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\ReplicateAction::make()
                ->beforeReplicaSaved(function ($replica): void {
                    $replica->title = $replica->title . ' (Copy)';
                    $replica->submitted_time = now();
                }),
        ];
    }
}