<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Course')
                ->icon('heroicon-o-plus'),
            
            Actions\Action::make('export')
                ->label('Export Courses')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(route('export.courses'))
                ->openUrlInNewTab(),
        ];
    }

    public function getTabs(): array
    {
        // Pre-aggregate category counts in a single query
        $counts = \App\Models\Course::query()
            ->selectRaw('category, COUNT(*) as count')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderBy('category')
            ->pluck('count', 'category');

        $total = \App\Models\Course::count();

        $tabs = [
            'all' => Tab::make('All Courses (' . $total . ')')
                ->modifyQueryUsing(fn (Builder $query) => $query),
        ];

        foreach ($counts as $cat => $count) {
            $tabs[$cat] = Tab::make($cat . ' (' . $count . ')')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', $cat));
        }

        return $tabs;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // CourseResource\Widgets\CourseStatsOverview::class,
        ];
    }
}