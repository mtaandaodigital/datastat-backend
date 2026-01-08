<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopCoursesWidget extends BaseWidget
{
    protected static ?string $heading = '5 Courses with Latest Registrants';
    
    protected static ?int $sort = 3;
    
    public static function canView(): bool
    {
        return auth()->check();
    }
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Course::query()
                    ->whereHas('registrants')
                    ->withCount('registrants')
                    ->with(['registrants' => function ($query) {
                        $query->orderByRaw('STR_TO_DATE(registered_time, "%W %D of %M %Y %h:%i:%s %p") DESC')->limit(1);
                    }])
                    ->orderByDesc(function ($query) {
                        $query->selectRaw('STR_TO_DATE(registered_time, "%W %D of %M %Y %h:%i:%s %p")')
                            ->from('registrants')
                            ->whereColumn('registrants.course_id', 'course_event.id')
                            ->orderByRaw('STR_TO_DATE(registered_time, "%W %D of %M %Y %h:%i:%s %p") DESC')
                            ->limit(1);
                    })
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Course Title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                    
                Tables\Columns\TextColumn::make('registrants_count')
                    ->label('Total Registrants')
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('latest_registration')
                    ->label('Latest Registration')
                    ->getStateUsing(function (Course $record) {
                        $latest = $record->registrants->first();
                        if (!$latest || !$latest->registered_time) {
                            return 'No registrations';
                        }
                        
                        try {
                            // Try to parse the custom date format
                            $date = \DateTime::createFromFormat('l jS \of F Y h:i:s A', $latest->registered_time);
                            if ($date) {
                                return $date->format('M j, Y g:i A');
                            }
                        } catch (\Exception $e) {
                            // If parsing fails, return the raw string
                        }
                        
                        return $latest->registered_time;
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Course')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Course $record): string => route('filament.admin.resources.courses.view', $record))
                    ->openUrlInNewTab(),
            ])
            ->paginated(false);
    }
}