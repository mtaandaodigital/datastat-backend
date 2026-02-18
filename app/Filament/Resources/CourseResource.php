<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Courses';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12)
                    ->schema([
                        // Left column
                        Forms\Components\Group::make([
                            Forms\Components\Section::make('Course Information')
                                ->schema([
                                    Forms\Components\TextInput::make('title')
                                        ->label('Course Title')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Select::make('category')
                                        ->label('Category')
                                        ->options(\App\Models\Category::query()->orderBy('category_name')->pluck('category_name', 'category_name')->toArray())
                                        ->required()
                                        ->searchable(),
                                    Forms\Components\Select::make('course_status')
                                        ->label('Course Status')
                                        ->options(Course::getCourseStatuses())
                                        ->required()
                                        ->default('Top'),
                                ]),

                            Forms\Components\Section::make('Course Content')
                                ->schema([
                                    Forms\Components\RichEditor::make('introduction')
                                        ->label('Course Introduction')
                                        ->required()
                                        ->extraAttributes(['class' => 'fi-editor-scroll'])
                                        ->toolbarButtons([
                                            'bold','italic','underline','strike','link','bulletList','orderedList','blockquote','h2','h3','undo','redo'
                                        ])
                                        ->helperText('Brief introduction that will appear in course previews and summaries'),
                                    Forms\Components\RichEditor::make('body')
                                        ->label('Course Content')
                                        ->required()
                                        ->extraAttributes(['class' => 'fi-editor-scroll'])
                                        ->toolbarButtons([
                                            'attachFiles','blockquote','bold','bulletList','codeBlock','h2','h3','italic','link','orderedList','redo','strike','underline','undo'
                                        ])
                                        ->helperText('Detailed course content and curriculum'),

                                    // Moved SEO & Meta Information directly below body, not collapsible
                                    Forms\Components\Section::make('SEO & Meta Information')
                                        ->schema([
                                            Forms\Components\Textarea::make('meta_description')
                                                ->label('Meta Description')
                                                ->rows(3)
                                                ->helperText('Brief description for search engines. This appears in search results.')
                                                ->placeholder('Enter a compelling description that summarizes this course...'),
                                            Forms\Components\Textarea::make('meta_keywords')
                                                ->label('Meta Keywords')
                                                ->rows(2)
                                                ->helperText('Comma-separated keywords for SEO (e.g., "data analysis, statistics, SPSS, research")')
                                                ->placeholder('keyword1, keyword2, keyword3...'),
                                        ]),
                                ]),
                        ])->columnSpan(6),

                        // Right column
                        Forms\Components\Group::make([
                            Forms\Components\Section::make('Course Image')
                                ->schema([
                                    Forms\Components\Grid::make()
                                        ->schema([
                                            // Current image preview
                                            Forms\Components\ViewField::make('current_image')
                                                ->label('Current Image')
                                                ->view('filament.components.course-image-preview')
                                                ->dehydrated(false)
                                                ->visible(function ($record) {
                                                    return $record && !empty($record->image_path);
                                                }),
                                                
                                            // Image upload field
                                            Forms\Components\FileUpload::make('image_path')
                                                ->label(function ($record) {
                                                    return $record && !empty($record->image_path) 
                                                        ? 'Update Image' 
                                                        : 'Upload Image';
                                                })
                                                ->image()
                                                ->disk('main_site_uploads') // Save directly to main site images/courses
                                                ->visibility('public')
                                                ->maxSize(400) // KB
                                                ->imageResizeMode('cover')
                                                ->imageCropAspectRatio('16:9')
                                                ->imageResizeTargetWidth('800')
                                                ->imageResizeTargetHeight('450')
                                                ->getUploadedFileNameForStorageUsing(function ($file, $get) {
                                                    $ext = $file->getClientOriginalExtension();
                                                    
                                                    // Get the title from the form
                                                    $title = $get('title');
                                                    
                                                    if ($title) {
                                                        $slug = \Illuminate\Support\Str::slug($title);
                                                    } else {
                                                        // Fallback to original filename
                                                        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                                                        $slug = \Illuminate\Support\Str::slug($name);
                                                    }
                                                    
                                                    return $slug . '-by-datastat-training-institute.' . $ext;
                                                })
                                                ->helperText('Upload a course image (recommended: 800x450px, max 400KB). Image will be saved to datastatresearch.org/images/courses directory.'),
                                        ]),
                                ]),

                            Forms\Components\Section::make('Course Requirements')
                                ->schema([
                                    Forms\Components\TextInput::make('software')
                                        ->label('Required Software')
                                        ->maxLength(255)
                                        ->helperText('Software requirements for the course (e.g., "SPSS, R Studio, Excel")'),
                                ]),

                            Forms\Components\Section::make('Schedule & Location')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\DatePicker::make('start')
                                                ->label('Start Date (Database Date Field)')
                                                ->required(),
                                            Forms\Components\TextInput::make('start_date')
                                                ->label('Start Date (Text Field)')
                                                ->helperText('Format: YYYY-MM-DD - Used for CSV imports'),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('end')
                                                ->label('End Date (Text Field)')
                                                ->required()
                                                ->default('2027-01-01')
                                                ->helperText('Format: YYYY-MM-DD'),
                                            Forms\Components\TextInput::make('cut_of_date')
                                                ->label('Registration Cutoff Date (Text Field)')
                                                ->required()
                                                ->helperText('Format: d/m/Y (e.g., 23/08/2025)')
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('no_of_days')
                                                ->label('Duration (Days)')
                                                ->numeric()
                                                ->required()
                                                ->minValue(1)
                                                ->maxValue(365),
                                            Forms\Components\TextInput::make('location')
                                                ->label('Location')
                                                ->required()
                                                ->maxLength(255),
                                        ]),
                                ]),

                            Forms\Components\Section::make('Pricing')
                                ->schema([
                                    Forms\Components\TextInput::make('fee_usd')
                                        ->label('Price (USD)')
                                        ->numeric()
                                        ->prefix('$')
                                        ->helperText('Course price in US Dollars'),
                                    Forms\Components\TextInput::make('fee_kes')
                                        ->label('Price (KES)')
                                        ->numeric()
                                        ->prefix('KES')
                                        ->helperText('Course price in Kenyan Shillings'),
                                    Forms\Components\TextInput::make('fee_euro')
                                        ->label('Price (EUR)')
                                        ->numeric()
                                        ->prefix('€')
                                        ->helperText('Course price in Euros'),
                                    Forms\Components\TextInput::make('fee_pound')
                                        ->label('Price (GBP)')
                                        ->numeric()
                                        ->prefix('£')
                                        ->helperText('Course price in British Pounds'),
                                ]),

                            Forms\Components\Section::make('Status')
                                ->schema([
                                    Forms\Components\Toggle::make('schedule_status')
                                        ->label('Active/Published')
                                        ->default(true)
                                        ->helperText('Only active courses will be visible to users')
                                        ->inline(false),
                                ]),



                            Forms\Components\Section::make('Schedules')
                                ->schema([
                                    Forms\Components\Placeholder::make('existing_schedules')
                                        ->label('Existing Schedules')
                                        ->content(function ($record) {
                                            if (!$record) {
                                                return 'No schedules available. Save the course first to view schedules.';
                                            }
                                            
                                            $schedules = \App\Models\Schedule::where('course_id', $record->id)
                                                ->orderByDesc('start')
                                                ->limit(5)
                                                ->get(['location', 'start', 'end', 'course_fee_usd', 'course_fee_ksh']);
                                            
                                            $total = \App\Models\Schedule::where('course_id', $record->id)->count();
                                            
                                            if ($schedules->isEmpty()) {
                                                $html = '<div class="text-gray-500 text-sm">No schedules found for this course.</div>';
                                                $html .= '<div class="mt-3">';
                                                $html .= '<button type="button" onclick="scrollToSchedules()" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-md transition-colors duration-200">';
                                                $html .= '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                                                $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8l-8 8-8-8"></path>';
                                                $html .= '</svg>';
                                                $html .= 'Manage Schedules';
                                                $html .= '</button>';
                                                $html .= '</div>';
                                            } else {
                                                $html = '<div class="space-y-2">';
                                                foreach ($schedules as $schedule) {
                                                    $html .= '<div class="p-2 bg-gray-50 dark:bg-gray-800 rounded text-sm">';
                                                    $html .= '<div class="font-medium">' . $schedule->location . '</div>';
                                                    $html .= '<div class="text-xs text-gray-500 dark:text-gray-400">';
                                                    $html .= 'Start: ' . ($schedule->start ? (is_string($schedule->start) ? date('M d, Y', strtotime($schedule->start)) : $schedule->start->format('M d, Y')) : 'N/A');
                                                    $html .= ' | End: ' . ($schedule->end ? (is_string($schedule->end) ? date('M d, Y', strtotime($schedule->end)) : $schedule->end->format('M d, Y')) : 'N/A');
                                                    $html .= '</div>';
                                                    $html .= '<div class="text-xs mt-1">';
                                                    $html .= 'USD: $' . number_format($schedule->course_fee_usd, 2);
                                                    $html .= ' | KES: ' . number_format($schedule->course_fee_ksh, 2);
                                                    $html .= '</div>';
                                                    $html .= '</div>';
                                                }
                                                $html .= '</div>';
                                                
                                                if ($total > 5) {
                                                    $html .= '<div class="mt-2 text-xs text-gray-500 dark:text-gray-400">';
                                                    $html .= 'Showing 5 of ' . $total . ' schedules. ';
                                                    $html .= '<button type="button" onclick="scrollToSchedules()" class="text-primary-600 hover:text-primary-800 font-medium">View all</button>';
                                                    $html .= '</div>';
                                                }
                                                
                                                $html .= '<script>';
                                                $html .= 'function scrollToSchedules() {';
                                                $html .= '  const schedulesTab = document.querySelector(\'[x-on\\:click*="tab=\\\'schedules\\\'"]\');';
                                                $html .= '  if (schedulesTab) {';
                                                $html .= '    schedulesTab.click();';
                                                $html .= '    setTimeout(() => {';
                                                $html .= '      window.scrollTo({';
                                                $html .= '        top: schedulesTab.offsetTop,';
                                                $html .= '        behavior: "smooth"';
                                                $html .= '        }';
                                                $html .= '      });';
                                                $html .= '    }, 1000);';
                                                $html .= '  }';
                                                $html .= '}';
                                                $html .= '</script>';
                                                
                                                return new \Illuminate\Support\HtmlString($html);
                                            }
                                        })
                                        ->helperText('View and manage course schedules. Use the Generate Schedules page to create new schedules.'),
                                ])
                                ->collapsible()
                                ->collapsed(),
                        ])->columnSpan(6),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->size(80)
                    ->height(45) // 16:9 aspect ratio
                    ->defaultImageUrl('https://datastatresearch.org/login/images/default-course.jpg'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Course Title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 40) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_text')
                    ->label('Duration')
                    ->alignCenter()
                    ->searchable(false)
                    ->sortable(false),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('schedule_status')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(\App\Models\Category::query()->orderBy('category_name')->pluck('category_name', 'category_name')->toArray())
                    ->label('Category'),
                
                Tables\Filters\SelectFilter::make('course_status')
                    ->options(Course::getCourseStatuses())
                    ->label('Course Status'),
                
                Tables\Filters\TernaryFilter::make('schedule_status')
                    ->label('Active Status')
                    ->placeholder('All Courses')
                    ->trueLabel('Active Courses')
                    ->falseLabel('Inactive Courses'),
                
                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming Courses')
                    ->query(fn (Builder $query): Builder => $query->where('start', '>=', now())),
                
                Tables\Filters\Filter::make('past')
                    ->label('Past Courses')
                    ->query(fn (Builder $query): Builder => $query->where('start', '<', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Delete course')
                    ->modalDescription('This will also delete all related schedules. This action cannot be undone.')
                    ->before(function (Course $record): void {
                        $record->schedules()->delete();
                    }),
                
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Course $record) {
                        $duplicate = $record->replicate();
                        $duplicate->title = $duplicate->title . ' (Copy)';
                        $duplicate->submitted_time = now();
                        $duplicate->save();
                        
                        return redirect()->route('filament.admin.resources.courses.edit', ['record' => $duplicate->id]);
                    }),
                
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Course $record): string => $record->schedule_status ? 'Deactivate' : 'Activate')
                    ->icon(fn (Course $record): string => $record->schedule_status ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Course $record): string => $record->schedule_status ? 'danger' : 'success')
                    ->action(function (Course $record): void {
                        $record->update(['schedule_status' => !$record->schedule_status]);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('deleteWithSchedules')
                        ->label('Delete')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete selected courses')
                        ->modalDescription('This will also delete all related schedules. This action cannot be undone.')
                        ->action(function ($records) {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($records) {
                                $records->each(function (\App\Models\Course $course) {
                                    $course->schedules()->delete();
                                    $course->delete();
                                });
                            });
                        }),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn($record) => $record->update(['schedule_status' => true]));
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn($record) => $record->update(['schedule_status' => false]));
                        }),

                    Tables\Actions\BulkAction::make('editCategory')
                        ->label('Change Category')
                        ->icon('heroicon-o-pencil-square')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('category')
                                ->label('Category')
                                ->options(fn() => \App\Models\Category::pluck('category_name', 'category_name')->toArray())
                                ->required()
                                ->placeholder('Select a category'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn($record) => $record->update(['category' => $data['category']]));
                        }),
                ]),
            ])
            ->defaultSort('id', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Course Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('title')
                                    ->label('Course Title')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->columnSpanFull(),

                                Infolists\Components\TextEntry::make('category')
                                    ->label('Category')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('course_status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Premium' => 'success',
                                        'VIP' => 'warning',
                                        'Corporate' => 'danger',
                                        default => 'info',
                                    }),
                            ]),

                        Infolists\Components\TextEntry::make('introduction')
                            ->label('Introduction')
                            ->html()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('body')
                            ->label('Content')
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Schedule Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('start')
                                    ->label('Start Date')
                                    ->date(),
                                Infolists\Components\TextEntry::make('end')
                                    ->label('End Date')
                                    ->date(),
                                Infolists\Components\TextEntry::make('cut_of_date')
                                    ->label('Registration Cutoff')
                                    ->formatStateUsing(function ($state) {
                                        if (empty($state)) return null;
                                        try {
                                            // Try d/m/Y first (how it's saved by imports)
                                            $dt = \Carbon\Carbon::createFromFormat('d/m/Y', (string) $state);
                                        } catch (\Throwable $e) {
                                            try {
                                                // Fallback to any parseable format (e.g., Y-m-d)
                                                $dt = \Carbon\Carbon::parse((string) $state);
                                            } catch (\Throwable $e2) {
                                                // As a last resort return raw value
                                                return (string) $state;
                                            }
                                        }
                                        return $dt->format('M d, Y');
                                    }),
                            ]),
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('no_of_days')
                                    ->label('Duration (Days)'),
                                Infolists\Components\TextEntry::make('location')
                                    ->label('Location'),
                                Infolists\Components\IconEntry::make('schedule_status')
                                    ->label('Active')
                                    ->boolean(),
                            ]),
                    ]),

                Infolists\Components\Section::make('Requirements')
                    ->schema([
                        Infolists\Components\TextEntry::make('software')
                            ->label('Required Software')
                            ->placeholder('None specified'),
                    ]),

                Infolists\Components\Section::make('Pricing')
                    ->schema([
                        Infolists\Components\TextEntry::make('formatted_price')
                            ->label('Prices')
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Course Image')
                    ->schema([
                        Infolists\Components\ImageEntry::make('image_url')
                            ->label('Course Image')
                            ->height('auto')
                            ->extraImgAttributes(['class' => 'rounded-lg border border-gray-200 shadow-sm']),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CourseResource\RelationManagers\SchedulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'view' => Pages\ViewCourse::route('/{record}'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}