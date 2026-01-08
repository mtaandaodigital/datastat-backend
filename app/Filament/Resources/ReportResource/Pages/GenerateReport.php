<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Models\Report;
use App\Models\Course;
use App\Services\ReportingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Actions;

class GenerateReport extends Page
{
    protected static string $resource = ReportResource::class;

    protected static string $view = 'filament.resources.report-resource.pages.generate-report';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Configuration')
                    ->description('Configure your report parameters and generate instantly')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Report Type')
                            ->options(Report::getTypes())
                            ->required()
                            ->reactive()
                            ->helperText('Select the type of report you want to generate'),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->required()
                                    ->default(now()->subMonths(3))
                                    ->helperText('Report data from this date'),
                                
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->required()
                                    ->default(now())
                                    ->after('start_date')
                                    ->helperText('Report data until this date'),
                            ]),
                        
                        Forms\Components\Select::make('course_id')
                            ->label('Specific Course (Optional)')
                            ->options(Course::active()->pluck('title', 'course_id'))
                            ->searchable()
                            ->nullable()
                            ->helperText('Leave empty for all courses')
                            ->visible(fn (Forms\Get $get): bool => 
                                in_array($get('type'), ['course_registrations', 'attendance_report', 'completion_rates'])
                            ),
                        
                        Forms\Components\Select::make('format')
                            ->label('Output Format')
                            ->options([
                                'csv' => 'CSV (Excel Compatible)',
                                'json' => 'JSON (Data Format)',
                            ])
                            ->default('csv')
                            ->required()
                            ->helperText('Choose the file format for your report'),
                    ]),
                
                Forms\Components\Section::make('Report Descriptions')
                    ->description('Learn about each report type')
                    ->schema([
                        Forms\Components\Placeholder::make('report_info')
                            ->content(view('filament.components.report-descriptions')),
                    ])
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('generate')
                ->label('Generate Report')
                ->icon('heroicon-o-chart-bar')
                ->color('primary')
                ->action('generateReport'),
        ];
    }

    public function generateReport(): void
    {
        $data = $this->form->getState();
        
        if (!$data['type']) {
            $this->addError('data.type', 'Please select a report type.');
            return;
        }

        try {
            $reportingService = new ReportingService();
            
            $parameters = [
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'format' => $data['format'] ?? 'csv',
            ];

            if (isset($data['course_id']) && $data['course_id']) {
                $parameters['course_id'] = $data['course_id'];
            }

            $result = $reportingService->generateReport($data['type'], $parameters);

            if ($result['success']) {
                $this->redirect(static::getResource()::getUrl('view', ['record' => $result['report_id']]));
            } else {
                $this->addError('data.type', $result['message']);
            }

        } catch (\Exception $e) {
            $this->addError('data.type', 'Report generation failed: ' . $e->getMessage());
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Reports')
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => static::getResource()::getUrl('index')),
        ];
    }
}