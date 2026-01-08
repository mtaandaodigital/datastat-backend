<?php

namespace App\Filament\Widgets;

use App\Models\Registrant;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RegistrationChart extends ChartWidget
{
    protected static ?string $heading = 'Registration Trends';
    
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
    }

    protected function getData(): array
    {
        // Get registration data for the last 12 months
        $data = [];
        $labels = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');
            
            $count = Registrant::whereRaw('YEAR(STR_TO_DATE(registered_time, "%W %D of %M %Y %h:%i:%s %p")) = ?', [$month->year])
                              ->whereRaw('MONTH(STR_TO_DATE(registered_time, "%W %D of %M %Y %h:%i:%s %p")) = ?', [$month->month])
                              ->count();
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'New Registrations',
                    'data' => $data,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}