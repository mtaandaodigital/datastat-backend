<?php

namespace App\Filament\Resources\SentEmailLogResource\Pages;

use App\Filament\Resources\SentEmailLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSentEmailLogs extends ListRecords
{
    protected static string $resource = SentEmailLogResource::class;

    protected function getHeaderActions(): array
    {
        // Use page header actions for Filament\Actions\Action instances.
        // Export is a table action; provide no header actions here to avoid type errors.
        return [];
    }
}
