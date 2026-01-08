<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('DataStat Research')
            // ->brandLogo(asset('images/logo.png'))
            // ->brandLogoHeight('2rem')
            ->colors([
                'primary' => [
                    50 => '#F8F7FF',   // Very light lavender
                    100 => '#F0EDFF',  // Light lavender
                    200 => '#E6E6FA',  // Lavender tint
                    300 => '#D8D0FF',  // Medium lavender
                    400 => '#C4B5FD',  // Darker lavender
                    500 => '#9370DB',  // Royal blue tint (main)
                    600 => '#7C3AED',  // Darker purple
                    700 => '#6D28D9',  // Deep purple
                    800 => '#5B21B7',  // Very deep purple
                    900 => '#4C1D95',  // Dark purple
                    950 => '#2E1065',  // Darkest purple
                ],
                'secondary' => [
                    50 => '#F0F9FF',   // Very light blue
                    100 => '#E0F2FE',  // Light blue
                    200 => '#BAE6FD',  // Light dodger blue
                    300 => '#87CEEB',  // Dodger blue tint (main)
                    400 => '#38BDF8',  // Medium blue
                    500 => '#0EA5E9',  // Dodger blue
                    600 => '#0284C7',  // Darker blue
                    700 => '#0369A1',  // Deep blue
                    800 => '#075985',  // Very deep blue
                    900 => '#0C4A6E',  // Dark blue
                    950 => '#082F49',  // Darkest blue
                ],
                'danger' => Color::Rose,
                'gray' => [
                    50 => '#FAFAFA',   // Almost white
                    100 => '#F5F5F5',  // Very light gray
                    200 => '#E5E5E5',  // Light gray
                    300 => '#D4D4D4',  // Medium light gray
                    400 => '#A3A3A3',  // Medium gray
                    500 => '#737373',  // Gray
                    600 => '#525252',  // Dark gray
                    700 => '#404040',  // Darker gray
                    800 => '#262626',  // Very dark gray
                    900 => '#171717',  // Almost black
                    950 => '#0A0A0A',  // Near black
                ],
                'info' => [
                    50 => '#F0F9FF',
                    100 => '#E0F2FE',
                    200 => '#BAE6FD',
                    300 => '#87CEEB',  // Dodger blue tint
                    400 => '#38BDF8',
                    500 => '#0EA5E9',
                    600 => '#0284C7',
                    700 => '#0369A1',
                    800 => '#075985',
                    900 => '#0C4A6E',
                    950 => '#082F49',
                ],
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\Calendar::class,
                \App\Filament\Pages\FunnelAnalytics::class,
                \App\Filament\Pages\GenerateReports::class, // page-only reports tool
                \App\Filament\Pages\GenerateSchedules::class,
                \App\Filament\Pages\UploadCoursesCsv::class,
            ])
            ->widgets([
                \App\Filament\Widgets\StatsOverview::class,
                \App\Filament\Widgets\RegistrationChart::class,
                \App\Filament\Widgets\CountryChart::class,
                \App\Filament\Widgets\TopCoursesWidget::class,
                \App\Filament\Widgets\LatestRegistrantsWidget::class,
                \App\Filament\Widgets\DayOfWeekChart::class,
                \App\Filament\Widgets\TimeOfDayChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web');
    }
}