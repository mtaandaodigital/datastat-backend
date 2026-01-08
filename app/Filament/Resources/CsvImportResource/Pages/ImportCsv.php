<?php

namespace App\Filament\Resources\CsvImportResource\Pages;

use App\Filament\Resources\CsvImportResource;
use App\Models\CsvImport;
use App\Services\CsvImportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImportCsv extends Page
{
    protected static string $resource = CsvImportResource::class;

    protected static string $view = 'filament.resources.csv-import-resource.pages.import-csv';

    public ?array $data = [];
    public ?CsvImport $currentImport = null;
    public bool $showProgress = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('CSV Import')
                    ->description('Upload a CSV file to import data into the system')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Import Type')
                            ->options([
                                'courses' => 'Courses',
                                'users' => 'Users',
                                'leads' => 'Leads',
                                'news' => 'News/Blog Articles',
                            ])
                            ->required()
                            ->helperText('Select what type of data you want to import'),
                        
                        Forms\Components\FileUpload::make('file')
                            ->label('CSV File')
                            ->acceptedFileTypes(['text/csv'])
                            ->maxSize(10240) // 10MB
                            ->required()
                            ->helperText('Upload a CSV file (max 10MB)')
                            ->disk('local')
                            ->directory('temp-uploads')
                            ->visibility('private'),
                        
                        Forms\Components\Toggle::make('has_header')
                            ->label('File has header row')
                            ->default(true)
                            ->helperText('Check if your CSV file has column headers in the first row'),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Import Notes')
                            ->rows(3)
                            ->helperText('Optional notes about this import'),
                    ]),
                
                Forms\Components\Section::make('CSV Templates')
                    ->description('Download template files to see the expected format')
                    ->schema([
                        Forms\Components\Placeholder::make('templates')
                            ->content(view('filament.components.csv-templates')),
                    ])
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Start Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->action('import'),
        ];
    }

    public function import(): void
    {
        $data = $this->form->getState();
        
        if (!$data['file']) {
            $this->addError('data.file', 'Please select a file to import.');
            return;
        }

        try {
            // Get file path from Filament FileUpload (returns path string when using disk/directory)
            $filePath = is_array($data['file']) ? $data['file'][0] : $data['file'];
            
            Log::info('CSV Import - File data received', ['file' => $filePath, 'type' => gettype($filePath)]);
            
            // Extract filename
            $originalFilename = basename($filePath);
            $filename = time() . '_' . $originalFilename;
            
            // Get source file path
            $sourcePath = Storage::disk('local')->path($filePath);
            
            // Ensure csv-imports directory exists
            $csvImportsDir = storage_path('app/csv-imports');
            if (!is_dir($csvImportsDir)) {
                mkdir($csvImportsDir, 0755, true);
            }
            
            // Target path
            $targetPath = $csvImportsDir . '/' . $filename;
            
            // Copy file to csv-imports directory
            if (file_exists($sourcePath)) {
                copy($sourcePath, $targetPath);
                Log::info('CSV file copied successfully', ['from' => $sourcePath, 'to' => $targetPath]);
            } else {
                throw new \Exception("Source file not found: " . $sourcePath);
            }

            // Create import record
            $import = CsvImport::create([
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'type' => $data['type'],
                'status' => 'pending',
                'user_id' => auth()->user()->usermanagementid,
            ]);

            // Set current import and show progress
            $this->currentImport = $import;
            $this->showProgress = true;

            // Start processing immediately using the service
            $this->processImportNow($import, [
                'has_header' => $data['has_header'] ?? true,
                'notes' => $data['notes'] ?? null,
            ]);

        } catch (\Exception $e) {
            $this->addError('data.file', 'Import failed: ' . $e->getMessage());
            Log::error('CSV Import failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
        }
    }

    public function processImportNow(CsvImport $import, array $options = []): void
    {
        try {
            $service = new \App\Services\CsvImportService();
            $result = $service->processImport($import, $options);
            
            // Refresh the import data
            $this->currentImport->refresh();
            
            if ($result['success']) {
                $this->dispatch('import-completed', [
                    'status' => 'completed',
                    'message' => $result['message']
                ]);
            } else {
                $this->dispatch('import-failed', [
                    'status' => 'failed', 
                    'message' => $result['message']
                ]);
            }
            
        } catch (\Exception $e) {
            $import->markAsFailed(['Exception: ' . $e->getMessage()]);
            $this->dispatch('import-failed', [
                'status' => 'failed',
                'message' => 'Import failed: ' . $e->getMessage()
            ]);
        }
    }

    public function resetImport(): void
    {
        $this->currentImport = null;
        $this->showProgress = false;
        $this->form->fill();
    }

    public function viewImport(): void
    {
        if ($this->currentImport) {
            $this->redirect(static::getResource()::getUrl('view', ['record' => $this->currentImport->id]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Imports')
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => static::getResource()::getUrl('index')),
        ];
    }
}