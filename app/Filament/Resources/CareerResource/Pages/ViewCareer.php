<?php

namespace App\Filament\Resources\CareerResource\Pages;

use App\Filament\Resources\CareerResource;
use App\Models\CourseEvent;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;

class ViewCareer extends ViewRecord
{
    protected static string $resource = CareerResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('add_skill')
                ->label('Add Skill')
                ->form([
                    \Filament\Forms\Components\Select::make('skill')
                        ->options(
                            CourseEvent::distinct()
                                ->whereNotIn('software', $this->record->skills->pluck('skill'))
                                ->pluck('software', 'software')
                                ->filter()
                        )
                        ->required()
                ])
                ->action(function (array $data): void {
                    $this->record->skills()->create([
                        'skill' => $data['skill']
                    ]);
                    $this->refreshInfolist();
                }),
            
            Action::make('edit')
                ->url(fn () => static::$resource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Career Details')
                    ->schema([
                        TextEntry::make('title'),
                        ImageEntry::make('image_path'),
                        TextEntry::make('description')->markdown(),
                        TextEntry::make('meta_description'),
                        TextEntry::make('meta_keywords'),
                    ]),
                
                Section::make('Skills')
                    ->schema([
                        TextEntry::make('skills.skill')
                            ->label('Required Skills')
                            ->listWithLineBreaks()
                            ->bulleted(),
                    ]),
            ]);
    }
}