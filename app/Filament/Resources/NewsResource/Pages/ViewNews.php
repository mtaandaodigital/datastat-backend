<?php

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNews extends ViewRecord
{
    protected static string $resource = NewsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\ReplicateAction::make()
                ->beforeReplicaSaved(function ($replica): void {
                    $replica->title = $replica->title . ' (Copy)';
                    $replica->slug = \Illuminate\Support\Str::slug($replica->title);
                    $replica->is_published = false;
                }),
        ];
    }
}