<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate')
                ->label('Generate Report')
                ->icon('heroicon-o-plus')
                ->url(fn (): string => static::getResource()::getUrl('generate')),
            
            Actions\CreateAction::make()
                ->label('Schedule Report')
                ->icon('heroicon-o-clock'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Reports'),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->completed()),
            'scheduled' => Tab::make('Scheduled')
                ->modifyQueryUsing(fn (Builder $query) => $query->scheduled()),
            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'processing')),
            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed')),
            'recent' => Tab::make('Recent (30 Days)')
                ->modifyQueryUsing(fn (Builder $query) => $query->recent(30)),
        ];
    }
}