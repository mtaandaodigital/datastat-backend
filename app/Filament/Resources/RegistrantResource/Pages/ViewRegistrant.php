<?php

namespace App\Filament\Resources\RegistrantResource\Pages;

use App\Filament\Resources\RegistrantResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRegistrant extends ViewRecord
{
    protected static string $resource = RegistrantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('send_confirmation')
                ->label('Send Confirmation')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->action(function () {
                    // Logic to send confirmation email
                    $this->record->update(['confirmation_sent' => true]);
                })
                ->visible(fn (): bool => !$this->record->confirmation_sent),
            
            Actions\Action::make('send_reminder')
                ->label('Send Reminder')
                ->icon('heroicon-o-bell')
                ->color('warning')
                ->action(function () {
                    $this->record->sendReminder();
                })
                ->visible(fn (): bool => 
                    !$this->record->reminder_sent && 
                    $this->record->payment_status === 'Paid'
                ),
        ];
    }
}