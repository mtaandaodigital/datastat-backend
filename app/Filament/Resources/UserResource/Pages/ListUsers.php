<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New User')
                ->icon('heroicon-o-plus'),
            
            Actions\Action::make('export')
                ->label('Export Users')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(route('export.users'))
                ->openUrlInNewTab()
                ->visible(fn() => auth()->user()->isSuperAdmin()),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Users'),
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('login_active_status', 1)),
            'inactive' => Tab::make('Inactive')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('login_active_status', 0)),
            'admins' => Tab::make('Super Admins')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('user_level', 1)),
        ];
    }
}