<?php

namespace App\Filament\Widgets;

use App\Models\Registrant;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TimeOfDayChart extends ChartWidget
{
    protected static ?string $heading = 'Registrations by Time of Day';
    
    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        return auth()->check();
    }

    protected function getData(): array
    {
        // Get registration counts by hour of day
        $data = Registrant::select(
            DB::raw('HOUR(STR_TO_DATE(registered_time, "%W %D of %M %Y %h:%i:%s %p")) as hour'),
            DB::raw('COUNT(*) as count')
        )
        ->whereNotNull('registered_time')
        ->where('registered_time', '!=', '')
        ->groupBy('hour')
        ->orderBy('hour')
        ->get();

        // Create 24-hour array
        $hours = [];
        $counts = array_fill(0, 24, 0);

        // Fill in the actual data
        foreach ($data as $item) {
            $counts[$item->hour] = $item->count;
        }

        // Create hour labels (12-hour format)
        for ($i = 0; $i < 24; $i++) {
            if ($i == 0) {
                $hours[] = '12 AM';
            } elseif ($i < 12) {
                $hours[] = $i . ' AM';
            } elseif ($i == 12) {
                $hours[] = '12 PM';
            } else {
                $hours[] = ($i - 12) . ' PM';
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Registrations',
                    'data' => $counts,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'borderColor' => 'rgb(99, 102, 241)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $hours,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
                'x' => [
                    'ticks' => [
                        'maxTicksLimit' => 12, // Show every other hour
                    ],
                ],
            ],
        ];
    }
}