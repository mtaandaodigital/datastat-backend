<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Services\ReportingService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->status !== 'completed'),
            
            Actions\Action::make('download')
                ->label('Download Report')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    if (!$this->record->file_path || !Storage::exists($this->record->file_path)) {
                        $this->notify('danger', 'Report file not found');
                        return;
                    }
                    
                    return Storage::download($this->record->file_path, basename($this->record->file_path));
                })
                ->visible(fn (): bool => 
                    $this->record->status === 'completed' && $this->record->file_path
                ),
            
            Actions\Action::make('regenerate')
                ->label('Regenerate Report')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Regenerate Report')
                ->modalDescription('This will generate a new version of this report with current data.')
                ->action(function () {
                    $reportingService = new ReportingService();
                    $parameters = array_merge($this->record->parameters ?? [], [
                        'format' => 'csv', // Default format for regeneration
                    ]);
                    
                    $result = $reportingService->generateReport($this->record->type, $parameters);
                    
                    if ($result['success']) {
                        // Update the existing record
                        $this->record->update([
                            'status' => 'completed',
                            'file_path' => $result['file_path'],
                            'file_size' => Storage::size($result['file_path']),
                            'generated_at' => now(),
                            'error_message' => null,
                        ]);
                        
                        $this->notify('success', 'Report regenerated successfully!');
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } else {
                        $this->notify('danger', 'Report regeneration failed: ' . $result['message']);
                    }
                }),
            
            Actions\Action::make('schedule')
                ->label('Schedule Report')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->action(function () {
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                })
                ->visible(fn (): bool => !$this->record->scheduled),
        ];
    }
}