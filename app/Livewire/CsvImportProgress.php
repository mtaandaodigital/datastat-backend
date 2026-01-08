<?php

namespace App\Livewire;

use App\Models\CsvImport;
use Livewire\Component;
use Livewire\Attributes\On;

class CsvImportProgress extends Component
{
    public CsvImport $import;
    public bool $showProgress = false;
    public int $refreshInterval = 1000; // 1 second

    public function mount(CsvImport $import)
    {
        $this->import = $import;
        $this->showProgress = in_array($import->status, ['pending', 'processing']);
    }

    public function getProgressData()
    {
        $this->import->refresh();
        
        return [
            'status' => $this->import->status,
            'total_rows' => $this->import->total_rows ?? 0,
            'processed_rows' => $this->import->processed_rows ?? 0,
            'successful_rows' => $this->import->successful_rows ?? 0,
            'failed_rows' => $this->import->failed_rows ?? 0,
            'progress_percentage' => $this->import->progress_percentage ?? 0,
            'success_rate' => $this->import->success_rate ?? 0,
            'duration' => $this->import->duration,
            'is_completed' => in_array($this->import->status, ['completed', 'failed']),
        ];
    }

    #[On('refresh-progress')]
    public function refreshProgress()
    {
        $this->import->refresh();
        
        if (in_array($this->import->status, ['completed', 'failed'])) {
            $this->showProgress = false;
            $this->dispatch('import-completed', [
                'status' => $this->import->status,
                'message' => $this->import->status === 'completed' 
                    ? "Import completed successfully! {$this->import->successful_rows} records imported."
                    : "Import failed. Please check the error details."
            ]);
        }
    }

    #[On('import-completed')]
    public function handleImportCompleted($data)
    {
        $this->import->refresh();
        $this->showProgress = false;
        
        // Add success notification
        \Filament\Notifications\Notification::make()
            ->title('Import Completed')
            ->body($data['message'])
            ->success()
            ->send();
    }

    #[On('import-failed')]
    public function handleImportFailed($data)
    {
        $this->import->refresh();
        $this->showProgress = false;
        
        // Add error notification
        \Filament\Notifications\Notification::make()
            ->title('Import Failed')
            ->body($data['message'])
            ->danger()
            ->send();
    }

    #[On('update-progress')]
    public function updateProgress($progressData)
    {
        // This method will be called by JavaScript with fresh progress data
        // The component will automatically re-render with the new data
        $this->import->refresh();
    }

    public function render()
    {
        $progressData = $this->getProgressData();
        
        return view('livewire.csv-import-progress', [
            'progressData' => $progressData
        ]);
    }
}
