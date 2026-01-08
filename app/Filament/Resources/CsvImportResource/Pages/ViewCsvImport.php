<?php

namespace App\Filament\Resources\CsvImportResource\Pages;

use App\Filament\Resources\CsvImportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCsvImport extends ViewRecord
{
    protected static string $resource = CsvImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_errors')
                ->label('Download Errors')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->action(function () {
                    $record = $this->record;
                    
                    if (!$record->errors || empty($record->errors)) {
                        return;
                    }
                    
                    $filename = 'import_errors_' . $record->id . '.json';
                    $content = json_encode($record->errors, JSON_PRETTY_PRINT);
                    
                    return response()->streamDownload(function () use ($content) {
                        echo $content;
                    }, $filename, [
                        'Content-Type' => 'application/json',
                    ]);
                })
                ->visible(fn (): bool => 
                    $this->record->status === 'failed' && !empty($this->record->errors)
                ),
        ];
    }
}