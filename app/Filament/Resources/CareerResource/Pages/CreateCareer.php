<?php

namespace App\Filament\Resources\CareerResource\Pages;

use App\Filament\Resources\CareerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCareer extends CreateRecord
{
    protected static string $resource = CareerResource::class;

    protected function afterCreate(): void
    {
        // Get the selected skills from the form data
        $skills = $this->data['skills'] ?? [];
        
        // Create the skills for the career
        foreach ($skills as $skill) {
            $this->record->skills()->create(['skill' => $skill]);
        }
    }
}