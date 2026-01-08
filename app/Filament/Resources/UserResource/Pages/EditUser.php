<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\ViewAction::make(),
        ];

        // Only allow deletion for super admins and prevent self-deletion
        if (auth()->user()->isSuperAdmin() && 
            $this->record->usermanagementid !== auth()->user()->usermanagementid) {
            $actions[] = Actions\DeleteAction::make();
        }

        return $actions;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'User updated successfully!';
    }
}