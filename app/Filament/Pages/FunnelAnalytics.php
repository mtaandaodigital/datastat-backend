<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Models\Registrant;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class FunnelAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static string $view = 'filament.pages.funnel-analytics';

    protected static ?string $title = 'Funnel Analytics';

    protected static ?string $navigationLabel = 'Funnel Analytics';

    protected static ?int $navigationSort = 3;

    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?string $status = null;

    public function mount(): void
    {
        $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('dateFrom')
                    ->label('From Date')
                    ->default(Carbon::now()->subDays(30))
                    ->reactive(),
                DatePicker::make('dateTo')
                    ->label('To Date')
                    ->default(Carbon::now())
                    ->reactive(),
                Select::make('status')
                    ->label('Lead Status')
                    ->options([
                        '' => 'All Statuses',
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'converted' => 'Converted',
                        'lost' => 'Lost',
                    ])
                    ->reactive(),
            ])
            ->columns(3);
    }

    public function getStats(): array
    {
        if (!Schema::hasTable('leads')) {
            return [
                'total' => 0,
                'new' => 0,
                'contacted' => 0,
                'converted' => 0,
                'rate' => 0,
            ];
        }

        $query = Lead::query();

        // Apply date filters
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $total = $query->count();
        
        // Get counts by status
        $newLeads = (clone $query)->where('status', 'new')->count();
        $contactedLeads = (clone $query)->where('status', 'contacted')->count();
        $convertedLeads = (clone $query)->where('status', 'converted')->count();

        // Calculate conversion rate
        $conversionRate = $total > 0 ? round(($convertedLeads / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'new' => $newLeads,
            'contacted' => $contactedLeads,
            'converted' => $convertedLeads,
            'rate' => $conversionRate,
        ];
    }

    public function noop(): void
    {
        // This method is called by the form but doesn't need to do anything
        // It's just to prevent form submission errors
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin() && Schema::hasTable('leads');
    }
}