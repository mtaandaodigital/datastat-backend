<?php

namespace App\Filament\Widgets;

use App\Models\Registrant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestRegistrantsWidget extends BaseWidget
{
    protected static ?string $heading = 'Latest 5 Registrants';
    
    protected static ?int $sort = 4;
    
    public static function canView(): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
    }
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Registrant::query()
                    ->with('course')
                    ->orderByRaw('STR_TO_DATE(registered_time, "%W %D of %M %Y %h:%i:%s %p") DESC')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['firstname', 'surname'])
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('surname', $direction)->orderBy('firstname', $direction);
                    }),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('course.title')
                    ->label('Course')
                    ->limit(40)
                    ->tooltip(function (Registrant $record) {
                        return $record->course?->title;
                    }),
                    
                Tables\Columns\TextColumn::make('country')
                    ->label('Country')
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('registered_time')
                    ->label('Registered')
                    ->getStateUsing(function (Registrant $record) {
                        if (!$record->registered_time) {
                            return 'Unknown';
                        }
                        
                        try {
                            // Try to parse the custom date format
                            $date = \DateTime::createFromFormat('l jS \of F Y h:i:s A', $record->registered_time);
                            if ($date) {
                                return $date->format('M j, Y g:i A');
                            }
                        } catch (\Exception $e) {
                            // If parsing fails, return the raw string
                        }
                        
                        return $record->registered_time;
                    })
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Details')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Registrant $record): string => route('filament.admin.resources.registrants.view', $record))
                    ->openUrlInNewTab(),
            ])
            ->paginated(false);
    }
}