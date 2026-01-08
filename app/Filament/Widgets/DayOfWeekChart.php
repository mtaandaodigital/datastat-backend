<?php

namespace App\Filament\Widgets;

use App\Models\Registrant;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DayOfWeekChart extends ChartWidget
{
    protected static ?string $heading = 'Registrations by Day of Week';
    
    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return auth()->check();
    }

    protected function getData(): array
    {
        // Get registration counts by day of week
        $data = Registrant::select(
            DB::raw('DAYNAME(STR_TO_DATE(registered_time, "%W %D of %M %Y %h:%i:%s %p")) as day_name'),
            DB::raw('DAYOFWEEK(STR_TO_DATE(registered_time, "%W %D of %M %Y %h:%i:%s %p")) as day_number'),
            DB::raw('COUNT(*) as count')
        )
        ->whereNotNull('registered_time')
        ->where('registered_time', '!=', '')
        ->groupBy('day_name', 'day_number')
        ->orderBy('day_number')
        ->get();

        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $counts = array_fill(0, 7, 0);

        foreach ($data as $item) {
            $dayIndex = $item->day_number - 1; // MySQL DAYOFWEEK returns 1-7, we need 0-6
            $counts[$dayIndex] = $item->count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Registrations',
                    'data' => $counts,
                    'backgroundColor' => [
                        'rgba(239, 68, 68, 0.8)',   // Sunday - Red
                        'rgba(59, 130, 246, 0.8)',  // Monday - Blue
                        'rgba(34, 197, 94, 0.8)',   // Tuesday - Green
                        'rgba(251, 191, 36, 0.8)',  // Wednesday - Yellow
                        'rgba(168, 85, 247, 0.8)',  // Thursday - Purple
                        'rgba(236, 72, 153, 0.8)',  // Friday - Pink
                        'rgba(14, 165, 233, 0.8)',  // Saturday - Sky
                    ],
                    'borderColor' => [
                        'rgb(239, 68, 68)',
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(251, 191, 36)',
                        'rgb(168, 85, 247)',
                        'rgb(236, 72, 153)',
                        'rgb(14, 165, 233)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $days,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}