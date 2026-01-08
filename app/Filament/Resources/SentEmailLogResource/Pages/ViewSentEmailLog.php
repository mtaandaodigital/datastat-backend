<?php

namespace App\Filament\Resources\SentEmailLogResource\Pages;

use App\Filament\Resources\SentEmailLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;

class ViewSentEmailLog extends ViewRecord
{
    protected static string $resource = SentEmailLogResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\Action::make('preview_stored')
                ->label('Preview Stored Email')
                ->url(route('admin.sent_email_logs.preview', ['id' => $this->record->id]))
                ->openUrlInNewTab(),
        ];

        if ($this->record->template_id) {
            $actions[] = Actions\Action::make('preview_template')
                ->label('Preview Template')
                ->url(route('admin.email_templates.preview', ['id' => $this->record->template_id, 'registrant' => $this->record->registrant_id]))
                ->openUrlInNewTab();
        }

        return $actions;
    }

    public function getFooterWidgets(): array
    {
        return [];
    }
}
