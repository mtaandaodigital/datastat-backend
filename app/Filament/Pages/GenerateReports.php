<?php
namespace App\Filament\Pages;

use App\Services\ReportingService;
use App\Models\Category;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class GenerateReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = 'Generate Reports';
    protected static ?string $slug = 'generate-reports';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationGroup = 'Data Management';

    protected static string $view = 'filament.pages.generate-reports';

    public ?array $formData = [];

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Configuration')
                    ->description('Select the type of report to generate and the date range')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Report Type')
                            ->options([
                                'course_registrations' => 'Course Registrations Detail',
                                'financial_summary' => 'Financial Summary',
                                'lead_conversion' => 'Lead Conversion Analysis',
                                'registrations_by_country' => 'Registrations by Country',
                                'registrations_summary' => 'Registrations Summary',
                                'courses_by_category' => 'Courses by Category',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('category')
                                    ->label('Category (if applicable)')
                                    ->options(fn() => Category::pluck('category_name', 'id')->toArray())
                                    ->placeholder('All Categories')
                                    ->visible(fn(Get $get) => $get('type') === 'courses_by_category')
                                    ->reactive(),

                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->required()
                                    ->default(fn() => now()->subMonths(3)),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->required()
                                    ->default(fn() => now())
                                    ->afterOrEqual('start_date'),
                            ]),

                        Forms\Components\Select::make('format')
                            ->label('Output Format')
                            ->options([
                                'csv' => 'CSV (Excel Compatible)',
                                'json' => 'JSON (Data Format)',
                                'pdf' => 'PDF (Print Friendly)',
                            ])
                            ->default('csv')
                            ->required(),
                    ])
            ])
            ->statePath('formData');
    }

    public function submit()
    {
        if (empty($this->formData['type'] ?? null)) {
            Notification::make()
                ->title('Missing report type')
                ->body('Please select a report type')
                ->danger()
                ->send();
            return;
        }

        $service = new ReportingService();
        $params = $this->formData ?? [];

        $result = $service->generateReport($params['type'], $params);

        if (!is_array($result) || !($result['success'] ?? false)) {
            Notification::make()
                ->title('Failed to generate report')
                ->body($result['message'] ?? 'An error occurred')
                ->danger()
                ->send();
            return;
        }

        $filePath = $result['file_path'] ?? null;

        if (!$filePath || !Storage::exists($filePath)) {
            Notification::make()
                ->title('Report generated, but file not found')
                ->danger()
                ->send();
            return;
        }

        return Storage::download($filePath, basename($filePath));
    }
}
