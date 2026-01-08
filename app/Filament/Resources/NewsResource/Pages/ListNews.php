<?php

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListNews extends ListRecords
{
    protected static string $resource = NewsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Article')
                ->icon('heroicon-o-plus'),
            
            Actions\Action::make('export')
                ->label('Export News')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(route('export.news'))
                ->openUrlInNewTab(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Articles'),
            'recent' => Tab::make('Recent')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('time', '>=', now()->subDays(30))),
        ];
    }
}