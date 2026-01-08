<?php

namespace App\Filament\Resources\TrainerResource\Pages;

use App\Filament\Resources\TrainerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTrainers extends ListRecords
{
    protected static string $resource = TrainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Trainer')
                ->icon('heroicon-o-plus'),
            
            Actions\Action::make('export')
                ->label('Export Trainers')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(route('export.trainers'))
                ->openUrlInNewTab(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Trainers'),
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->active()),
            'available' => Tab::make('Available')
                ->modifyQueryUsing(fn (Builder $query) => $query->available()),
            'experienced' => Tab::make('Experienced (5+ years)')
                ->modifyQueryUsing(fn (Builder $query) => $query->byExperience(5)),
        ];
    }
}