<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReport extends EditRecord
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn() => auth()->user()->isSuperAdmin()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Report updated successfully!';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Prepare parameters array
        $parameters = $this->record->parameters ?? [];
        
        if (isset($data['start_date'])) {
            $parameters['start_date'] = $data['start_date'];
            unset($data['start_date']);
        }
        
        if (isset($data['end_date'])) {
            $parameters['end_date'] = $data['end_date'];
            unset($data['end_date']);
        }
        
        if (isset($data['course_id'])) {
            $parameters['course_id'] = $data['course_id'];
            unset($data['course_id']);
        }
        
        if (isset($data['format'])) {
            $parameters['format'] = $data['format'];
            unset($data['format']);
        }

        $data['parameters'] = $parameters;

        return $data;
    }
}