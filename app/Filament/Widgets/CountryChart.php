<?php

namespace App\Filament\Widgets;

use App\Models\Registrant;
use Filament\Widgets\ChartWidget;

class CountryChart extends ChartWidget
{
    protected static ?string $heading = 'Registrants by Country';
    
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
    }

    protected function getData(): array
    {
        // Get registrant data grouped by country
        $countryData = Registrant::selectRaw('country, COUNT(*) as count')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->limit(10) // Show top 10 countries
            ->get();

        $labels = [];
        $data = [];
        $backgroundColors = [
            'rgba(34, 197, 94, 0.8)',   // Green
            'rgba(59, 130, 246, 0.8)',  // Blue
            'rgba(239, 68, 68, 0.8)',   // Red
            'rgba(245, 158, 11, 0.8)',  // Amber
            'rgba(139, 92, 246, 0.8)',  // Purple
            'rgba(236, 72, 153, 0.8)',  // Pink
            'rgba(20, 184, 166, 0.8)',  // Teal
            'rgba(251, 146, 60, 0.8)',  // Orange
            'rgba(168, 85, 247, 0.8)',  // Violet
            'rgba(34, 211, 238, 0.8)',  // Cyan
        ];

        foreach ($countryData as $index => $item) {
            $labels[] = $item->country ?: 'Unknown';
            $data[] = $item->count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Registrants',
                    'data' => $data,
                    'backgroundColor' => array_slice($backgroundColors, 0, count($data)),
                    'borderColor' => array_map(function($color) {
                        return str_replace('0.8', '1', $color);
                    }, array_slice($backgroundColors, 0, count($data))),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y', // This makes it horizontal
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }


}