<?php

namespace App\Filament\Pages;

use App\Models\Course;
use App\Models\Schedule;
use App\Services\ScheduleGenerationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Database\Eloquent\Builder;

class GenerateSchedules extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Generate Schedules';
    protected static ?string $slug = 'generate-schedules';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationGroup = 'Data Management';

    protected static string $view = 'filament.pages.generate-schedules';

    public ?array $data = [];

    public function mount(): void
    {
        if (empty($this->data) || empty($this->data['towns'])) {
            $this->data = [
                'period_start' => '2026-01-01',
                'period_end' => '2026-12-31',
                'towns' => $this->getDefaultTowns(),
            ];
        }
        // Ensure form reflects defaults on first load
        $this->form->fill($this->data);
    }

    protected function getDefaultTowns(): array
    {
        return [
            // Nairobi pricing
            ['location' => 'Nairobi', 'price_usd_5days' => 1500, 'price_ksh_5days' => 110000, 'price_usd_10days' => 3000, 'price_ksh_10days' => 220000, 'enabled' => true],

            // International locations
            ['location' => 'Kigali', 'price_usd_5days' => 3000, 'price_ksh_5days' => 110000, 'price_usd_10days' => 6000, 'price_ksh_10days' => 220000, 'enabled' => true],
            ['location' => 'Dubai', 'price_usd_5days' => 5000, 'price_ksh_5days' => 110000, 'price_usd_10days' => 10000, 'price_ksh_10days' => 220000, 'enabled' => true],
            ['location' => 'Cape Town', 'price_usd_5days' => 4500, 'price_ksh_5days' => 110000, 'price_usd_10days' => 9000, 'price_ksh_10days' => 220000, 'enabled' => true],
            ['location' => 'Addis Ababa', 'price_usd_5days' => 3000, 'price_ksh_5days' => 110000, 'price_usd_10days' => 6000, 'price_ksh_10days' => 220000, 'enabled' => true],
            ['location' => 'Singapore', 'price_usd_5days' => 5000, 'price_ksh_5days' => 110000, 'price_usd_10days' => 10000, 'price_ksh_10days' => 220000, 'enabled' => true],
            ['location' => 'Istanbul', 'price_usd_5days' => 5000, 'price_ksh_5days' => 110000, 'price_usd_10days' => 10000, 'price_ksh_10days' => 220000, 'enabled' => true],
            ['location' => 'Bangkok', 'price_usd_5days' => 5000, 'price_ksh_5days' => 110000, 'price_usd_10days' => 10000, 'price_ksh_10days' => 220000, 'enabled' => true],
            ['location' => 'Accra', 'price_usd_5days' => 4000, 'price_ksh_5days' => 110000, 'price_usd_10days' => 8000, 'price_ksh_10days' => 220000, 'enabled' => true],
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Schedule Generation Period')
                    ->description('Set the date range for generating consecutive schedules. Schedules will start from the first Monday on/after the start date and continue consecutively (each new schedule starts the Monday after the previous ends) until the end date.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('period_start')
                                    ->label('Period Start Date')
                                    ->helperText('First schedule will start from the first Monday on/after this date')
                                    ->default('2026-01-01')
                                    ->displayFormat('d/m/Y')
                                    ->format('Y-m-d'),
                                Forms\Components\DatePicker::make('period_end')
                                    ->label('Period End Date')
                                    ->helperText('Last schedule must end on/before this date')
                                    ->default('2026-12-31')
                                    ->displayFormat('d/m/Y')
                                    ->format('Y-m-d')
                                    ->after('period_start'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),
                    
                Forms\Components\Section::make('Towns & Pricing Configuration')
                    ->description('Configure the towns and pricing that will be used when generating schedules for selected courses.')
                    ->schema([
                        Forms\Components\Repeater::make('towns')
                            ->default($this->getDefaultTowns())
                            ->schema([
                                Forms\Components\Grid::make(6)
                                    ->schema([
                                        Forms\Components\Toggle::make('enabled')
                                            ->label('Enabled')
                                            ->default(true)
                                            ->inline(false)
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('location')
                                            ->label('Town/Location')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('price_usd_5days')
                                            ->label('USD (5 days)')
                                            ->numeric()
                                            ->required()
                                            ->prefix('$')
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('price_ksh_5days')
                                            ->label('KES (5 days)')
                                            ->numeric()
                                            ->required()
                                            ->prefix('KES')
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('price_usd_10days')
                                            ->label('USD (10 days)')
                                            ->numeric()
                                            ->required()
                                            ->prefix('$')
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('price_ksh_10days')
                                            ->label('KES (10 days)')
                                            ->numeric()
                                            ->required()
                                            ->prefix('KES')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->createItemButtonLabel('Add Town')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['location'] ?? null),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getTableQuery(): Builder
    {
        return Course::query()->orderByDesc('id');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Course')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),
                    
                Tables\Columns\TextColumn::make('no_of_days')
                    ->label('Days')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('existing_towns')
                    ->label('Existing Towns')
                    ->state(function (Course $record) {
                        $towns = Schedule::query()
                            ->where('course_id', $record->id)
                            ->distinct()
                            ->orderBy('location')
                            ->pluck('location')
                            ->toArray();
                        return empty($towns) ? 'No schedules yet' : implode(', ', $towns);
                    })
                    ->limit(60)
                    ->tooltip(fn ($state) => $state)
                    ->color(fn ($state) => $state === 'No schedules yet' ? 'gray' : 'success'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_schedules')
                    ->label('View Schedules')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->modalHeading(fn (Course $record) => 'Existing Schedules for: ' . $record->title)
                    ->modalContent(function (Course $record) {
                        $schedules = Schedule::query()
                            ->where('course_id', $record->id)
                            ->orderByDesc('start_data')
                            ->limit(100)
                            ->get(['location','start','end','start_data','course_fee_usd','course_fee_ksh']);
                        
                        return view('filament.pages.partials.existing-schedules-modal', [
                            'course' => $record,
                            'schedules' => $schedules,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('4xl'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options(function () {
                        return Course::query()
                            ->select('category')
                            ->whereNotNull('category')
                            ->where('category', '!=', '')
                            ->groupBy('category')
                            ->orderBy('category')
                            ->pluck('category', 'category')
                            ->toArray();
                    })
                    ->searchable(),
                    
                Tables\Filters\Filter::make('has_schedules')
                    ->label('Has Schedules')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereExists(function ($query) {
                            $query->select(\DB::raw(1))
                                ->from('course_schedule')
                                ->whereColumn('course_schedule.course_id', 'course_event.id');
                        })
                    ),
                    
                Tables\Filters\Filter::make('no_schedules')
                    ->label('No Schedules')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNotExists(function ($query) {
                            $query->select(\DB::raw(1))
                                ->from('course_schedule')
                                ->whereColumn('course_schedule.course_id', 'course_event.id');
                        })
                    ),
            ])
            ->paginated([10, 25, 50, 100])
            ->bulkActions([
                Tables\Actions\BulkAction::make('generate')
                    ->label('Generate Schedules for Selected')
                    ->icon('heroicon-o-calendar-days')
                    ->modalHeading('Confirm schedules generation')
                    ->modalSubmitActionLabel('Generate')
                    ->modalCancelActionLabel('Cancel')
                    ->modalContent(function ($records) {
                        $service = new ScheduleGenerationService();
                        $rows = [];
                        
                        // Get period settings
                        $periodStart = !empty($this->data['period_start']) ? \Carbon\Carbon::createFromFormat('Y-m-d', $this->data['period_start']) : null;
                        $periodEnd = !empty($this->data['period_end']) ? \Carbon\Carbon::createFromFormat('Y-m-d', $this->data['period_end']) : null;
                        
                        foreach ($records as $course) {
                            $noOfDays = (int) ($course->no_of_days ?? 5);
                            
                            if ($periodStart && $periodEnd) {
                                $ranges = $service->generateScheduleDateRanges($noOfDays, $periodStart, $periodEnd);
                                $rangeText = count($ranges) . ' consecutive schedules from ' . $periodStart->format('d/m/Y') . ' to ' . $periodEnd->format('d/m/Y');
                            } else {
                                $range = $service->generateScheduleDates($noOfDays);
                                $rangeText = $range['start'] . ' - ' . $range['end'];
                            }
                            
                            $rows[] = [
                                'title' => $course->title,
                                'range' => $rangeText,
                            ];
                        }
                        
                        $towns = array_values(array_filter(array_map(fn ($t) => $t['location'] ?? '', $this->data['towns'] ?? [])));
                        
                        return view('filament.pages.partials.generate-schedules-preview', [
                            'count' => count($records),
                            'rows' => array_slice($rows, 0, 25),
                            'more' => max(0, count($rows) - 25),
                            'towns' => $towns,
                            'period_info' => $periodStart && $periodEnd ? 
                                "Period: {$periodStart->format('d/m/Y')} to {$periodEnd->format('d/m/Y')} (consecutive schedules)" : 
                                "Using default next available dates",
                        ]);
                    })
                    ->form([
                        Forms\Components\Toggle::make('skip_same_range')->label('Skip if last schedule matches computed range')->default(true),
                        Forms\Components\Radio::make('mode')
                            ->label('Mode')
                            ->options([
                                'append' => 'Append (keep existing schedules)',
                                'overwrite' => 'Overwrite (delete existing schedules for selected towns first)',
                            ])->default('append'),
                    ])
                    ->action(function ($records, array $data) {
                        $towns = $this->data['towns'] ?? [];
                        if (empty($towns)) {
                            Notification::make()->title('Please configure at least one town')->warning()->send();
                            return;
                        }
                        
                        $service = new ScheduleGenerationService();
                        $total = 0; $skipped = 0; $deleted = 0; $errors = [];
                        
                        // Prepare options with date range if provided
                        $options = [
                            'skip_same_range' => (bool) ($data['skip_same_range'] ?? true),
                            'mode' => $data['mode'] ?? 'append',
                        ];
                        
                        // Add period settings if provided
                        if (!empty($this->data['period_start']) && !empty($this->data['period_end'])) {
                            $options['period_start'] = $this->data['period_start'];
                            $options['period_end'] = $this->data['period_end'];
                        }
                        
                        foreach ($records as $course) {
                            $res = $service->generateForCourse($course, $towns, $options);
                            $total += $res['created'];
                            $skipped += $res['skipped'];
                            $deleted += $res['deleted'];
                            $errors = array_merge($errors, $res['errors']);
                        }
                        
                        $title = $total.' created';
                        if ($skipped) $title .= ", $skipped skipped";
                        if ($deleted) $title .= ", $deleted deleted";
                        $note = Notification::make()->title($title)->success();
                        if (!empty($errors)) $note->body(implode("\n", array_slice($errors, 0, 5)));
                        $note->send();
                    })
            ]);
    }
}

