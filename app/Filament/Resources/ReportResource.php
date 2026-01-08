<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use App\Models\Course;
use App\Services\ReportingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Reports';


	    public static function shouldRegisterNavigation(): bool
	    {
	        return false; // Hide Reports, since no reports table exists
	    }

	    public static function canViewAny(): bool
	    {
	        return false; // Disallow access to prevent queries against non-existent table
	    }

    protected static ?string $navigationGroup = 'Data Management';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Report Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2),

                        Forms\Components\Select::make('type')
                            ->label('Report Type')
                            ->options(Report::getTypes())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('name', Report::getTypes()[$state] ?? '');
                            }),
                    ]),

                Forms\Components\Section::make('Parameters')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->required()
                                    ->default(now()->subMonths(3)),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->required()
                                    ->default(now())
                                    ->after('start_date'),
                            ]),

                        Forms\Components\Select::make('course_id')
                            ->label('Specific Course (Optional)')
                            ->options(Course::active()->pluck('title', 'course_id'))
                            ->searchable()
                            ->nullable()
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
                            ->required(),
                    ]),

                Forms\Components\Section::make('Scheduling (Optional)')
                    ->schema([
                        Forms\Components\Toggle::make('scheduled')
                            ->label('Schedule Report')
                            ->reactive(),

                        Forms\Components\Select::make('schedule_frequency')
                            ->label('Frequency')
                            ->options(Report::getScheduleFrequencies())
                            ->visible(fn (Forms\Get $get): bool => $get('scheduled')),

                        Forms\Components\DateTimePicker::make('next_run_at')
                            ->label('Next Run Date')
                            ->visible(fn (Forms\Get $get): bool => $get('scheduled')),

                        Forms\Components\TagsInput::make('recipients')
                            ->label('Email Recipients')
                            ->placeholder('Enter email addresses')
                            ->visible(fn (Forms\Get $get): bool => $get('scheduled')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Report Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'financial_summary' => 'success',
                        'course_registrations' => 'info',
                        'trainer_performance' => 'warning',
                        'lead_conversion' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'pending' => 'gray',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('file_size_human')
                    ->label('File Size')
                    ->placeholder('N/A'),

                Tables\Columns\IconColumn::make('scheduled')
                    ->label('Scheduled')
                    ->boolean()
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('info')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Generated By')
                    ->placeholder('System'),

                Tables\Columns\TextColumn::make('generated_at')
                    ->label('Generated')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not generated'),

                Tables\Columns\TextColumn::make('next_run_at')
                    ->label('Next Run')
                    ->dateTime()
                    ->placeholder('Not scheduled')
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Report Type')
                    ->options(Report::getTypes()),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Report::getStatuses()),

                Tables\Filters\Filter::make('scheduled')
                    ->label('Scheduled Reports')
                    ->query(fn (Builder $query): Builder => $query->scheduled()),

                Tables\Filters\Filter::make('completed')
                    ->label('Completed Reports')
                    ->query(fn (Builder $query): Builder => $query->completed()),

                Tables\Filters\Filter::make('recent')
                    ->label('Recent (Last 30 Days)')
                    ->query(fn (Builder $query): Builder => $query->recent(30)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Report $record): bool => $record->status !== 'completed'),

                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (Report $record) {
                        if (!$record->file_path || !Storage::exists($record->file_path)) {
                            return;
                        }

                        return Storage::download($record->file_path, basename($record->file_path));
                    })
                    ->visible(fn (Report $record): bool =>
                        $record->status === 'completed' && $record->file_path
                    ),

                Tables\Actions\Action::make('regenerate')
                    ->label('Regenerate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Report $record) {
                        $reportingService = new ReportingService();
                        $parameters = array_merge($record->parameters ?? [], [
                            'format' => 'csv', // Default format for regeneration
                        ]);

                        $result = $reportingService->generateReport($record->type, $parameters);

                        if ($result['success']) {
                            // Update the existing record
                            $record->update([
                                'status' => 'completed',
                                'file_path' => $result['file_path'],
                                'generated_at' => now(),
                                'error_message' => null,
                            ]);
                        }
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->isSuperAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->isSuperAdmin()),
                ]),
            ])
            ->defaultSort('id', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Report Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Report Name')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('type')
                                    ->label('Report Type')
                                    ->badge(),
                            ]),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('No description provided')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Status & Progress')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'processing' => 'warning',
                                        'pending' => 'gray',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('file_size_human')
                                    ->label('File Size')
                                    ->placeholder('Not generated'),

                                Infolists\Components\TextEntry::make('user.full_name')
                                    ->label('Generated By')
                                    ->placeholder('System'),
                            ]),

                        Infolists\Components\TextEntry::make('error_message')
                            ->label('Error Message')
                            ->color('danger')
                            ->visible(fn ($record): bool => $record->status === 'failed' && $record->error_message),
                    ]),

                Infolists\Components\Section::make('Parameters')
                    ->schema([
                        Infolists\Components\TextEntry::make('parameters')
                            ->label('Report Parameters')
                            ->formatStateUsing(function ($state) {
                                if (!$state || !is_array($state)) {
                                    return 'No parameters';
                                }

                                $formatted = [];
                                foreach ($state as $key => $value) {
                                    $formatted[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
                                }

                                return implode("\n", $formatted);
                            })
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Schedule Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\IconEntry::make('scheduled')
                                    ->label('Scheduled Report')
                                    ->boolean(),

                                Infolists\Components\TextEntry::make('schedule_frequency')
                                    ->label('Frequency')
                                    ->placeholder('Not scheduled'),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('next_run_at')
                                    ->label('Next Run')
                                    ->dateTime()
                                    ->placeholder('Not scheduled'),

                                Infolists\Components\TextEntry::make('recipients')
                                    ->label('Email Recipients')
                                    ->listWithLineBreaks()
                                    ->placeholder('No recipients'),
                            ]),
                    ])
                    ->visible(fn ($record): bool => $record->scheduled),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime(),

                                Infolists\Components\TextEntry::make('generated_at')
                                    ->label('Generated At')
                                    ->dateTime()
                                    ->placeholder('Not generated'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'view' => Pages\ViewReport::route('/{record}'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
            'generate' => Pages\GenerateReport::route('/generate'),
        ];
    }

}