<?php

namespace App\Filament\Widgets;

use App\Models\Registrant;
use App\Models\Course;
use App\Models\Lead;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return auth()->check();
    }

    protected function getStats(): array
    {
        // Total registrants
        $totalRegistrants = Registrant::count();
        
        // Registrants this month - using raw SQL to handle the date format
        $thisMonth = Registrant::whereRaw('YEAR(STR_TO_DATE(registered_time, "%W %D of %M %Y %h:%i:%s %p")) = ?', [Carbon::now()->year])
            ->whereRaw('MONTH(STR_TO_DATE(registered_time, "%W %D of %M %Y %h:%i:%s %p")) = ?', [Carbon::now()->month])
            ->count();
            
        // Registrants last month
        $lastMonth = Registrant::whereRaw('YEAR(STR_TO_DATE(registered_time, "%W %D of %M %Y %h:%i:%s %p")) = ?', [Carbon::now()->subMonth()->year])
            ->whereRaw('MONTH(STR_TO_DATE(registered_time, "%W %D of %M %Y %h:%i:%s %p")) = ?', [Carbon::now()->subMonth()->month])
            ->count();
            
        // Calculate percentage change
        $monthlyChange = $lastMonth > 0 ? (($thisMonth - $lastMonth) / $lastMonth) * 100 : 0;
        $monthlyChangeFormatted = $monthlyChange > 0 ? '+' . number_format($monthlyChange, 1) . '%' : number_format($monthlyChange, 1) . '%';
        
        // Total courses
        $totalCourses = Course::count();
        
        // Active courses (assuming courses with registrants are active)
        $activeCourses = Course::whereHas('registrants')->count();
        
        // Countries with registrants
        $countriesWithRegistrants = Registrant::whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct('country')
            ->count();

        $isSuper = auth()->user()?->isSuperAdmin() === true;

        if (! $isSuper) {
            // Regular users: show only Total Courses
            return [
                Stat::make('Total Courses', number_format($totalCourses))
                    ->description(number_format($activeCourses) . ' with registrants')
                    ->descriptionIcon('heroicon-m-academic-cap')
                    ->color('info'),
            ];
        }

        // Super admins: show full stats
        return [
            Stat::make('Total Registrants', number_format($totalRegistrants))
                ->description('All time registrations')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
                
            Stat::make('This Month', number_format($thisMonth))
                ->description($monthlyChangeFormatted . ' from last month')
                ->descriptionIcon($monthlyChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthlyChange >= 0 ? 'success' : 'danger'),
                
            Stat::make('Total Courses', number_format($totalCourses))
                ->description(number_format($activeCourses) . ' with registrants')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),
                
            Stat::make('Countries', number_format($countriesWithRegistrants))
                ->description('With registrants')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('warning'),
        ];
    }
}