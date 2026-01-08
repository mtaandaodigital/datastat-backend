<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Lead')
                ->icon('heroicon-o-plus'),
            
            Actions\Action::make('export')
                ->label('Export Leads')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(route('export.leads'))
                ->openUrlInNewTab(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Leads'),
            'new' => Tab::make('New')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'New')),
            'contacted' => Tab::make('Contacted')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Contacted')),
            'qualified' => Tab::make('Qualified')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Qualified')),
            'converted' => Tab::make('Converted')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Converted')),
            'stale' => Tab::make('Stale')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->where('last_contacted_at', '<', now()->subDays(14))
                      ->orWhere(function ($q2) {
                          $q2->whereNull('last_contacted_at')
                             ->where('created_at', '<', now()->subDays(7));
                      });
                })),
        ];
    }
}