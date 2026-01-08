<?php

namespace App\Filament\Resources\RegistrantResource\Pages;

use App\Filament\Resources\RegistrantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRegistrants extends ListRecords
{
    protected static string $resource = RegistrantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Registration')
                ->icon('heroicon-o-plus'),
            
            Actions\Action::make('export')
                ->label('Export Registrants')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(route('export.registrants'))
                ->openUrlInNewTab(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Registrants'),
            'paid' => Tab::make('Paid')
                ->modifyQueryUsing(fn (Builder $query) => $query->paid()),
            'pending' => Tab::make('Pending Payment')
                ->modifyQueryUsing(fn (Builder $query) => $query->pending()),
            // 'upcoming' tab removed - no supporting scope/columns in schema
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->completed()),
            // tabs requiring non-existent columns removed (certificates/reminders)
        ];
    }
}