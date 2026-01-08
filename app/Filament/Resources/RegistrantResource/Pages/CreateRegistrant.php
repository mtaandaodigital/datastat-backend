<?php

namespace App\Filament\Resources\RegistrantResource\Pages;

use App\Filament\Resources\RegistrantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRegistrant extends CreateRecord
{
    protected static string $resource = RegistrantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registration created successfully!';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Align to existing schema: mirror amount fields to total_amount
        if (isset($data['final_amount']) && ($data['final_amount'] ?? 0) > 0) {
            $data['total_amount'] = $data['final_amount'];
        } elseif (isset($data['amount_paid']) && ($data['amount_paid'] ?? 0) > 0) {
            $data['total_amount'] = $data['amount_paid'];
        }

        return $data;
    }
}