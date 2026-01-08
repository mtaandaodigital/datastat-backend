<?php

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNews extends EditRecord
{
    protected static string $resource = NewsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\ReplicateAction::make()
                ->beforeReplicaSaved(function ($replica): void {
                    $replica->title = $replica->title . ' (Copy)';
                    $replica->slug = \Illuminate\Support\Str::slug($replica->title);
                    $replica->is_published = false;
                }),
            Actions\DeleteAction::make()
                ->visible(fn() => auth()->user()->isSuperAdmin()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Article updated successfully!';
    }
}