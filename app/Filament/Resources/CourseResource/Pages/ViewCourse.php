<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewCourse extends ViewRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->modalHeading('Download course PDF')
                ->form([
                    Forms\Components\DatePicker::make('cutoff')
                        ->label('Include schedules up to')
                        ->placeholder('YYYY-MM-DD')
                        ->default(now()->format('Y-12-31'))
                        ->rules(['required', 'date', 'after_or_equal:today']),

                    Forms\Components\Select::make('location')
                        ->label('Location')
                        ->options(function () {
                            $record = $this->getRecord();
                            if (! $record) return [];
                            // Always source locations from schedules table for this course
                            $locations = $record->schedules()->pluck('location')->filter()->unique()->values()->all();
                            $opts = ['' => 'All locations'];
                            foreach ($locations as $loc) {
                                $opts[$loc] = $loc;
                            }
                            return $opts;
                        })
                        ->searchable()
                        ->placeholder('All locations'),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();

                    // Build schedules query to check existence for the chosen options
                    $cutoff = $data['cutoff'] ?? null;
                    $location = $data['location'] ?? null;

                    $q = $record->schedules()->whereDate('start', '<=', $cutoff ?: now()->format('Y-m-d'));
                    if (!empty($location)) {
                        $q->where('location', $location);
                    }

                    $count = $q->count();

                    if ($count === 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('No schedules found')
                            ->body('There are no schedules for the selected cutoff date and location. Please adjust your selection.')
                            ->warning()
                            ->send();

                        return null; // keep modal open
                    }

                    $query = [];
                    if (!empty($data['cutoff'])) $query['cutoff'] = $data['cutoff'];
                    if (!empty($data['location'])) $query['location'] = $data['location'];

                    $url = route('admin.courses.pdf', $record);
                    if (!empty($query)) $url .= '?' . http_build_query($query);

                    return redirect($url);
                })
                ->openUrlInNewTab(),
            Actions\ReplicateAction::make()
                ->beforeReplicaSaved(function ($replica): void {
                    $replica->title = $replica->title . ' (Copy)';
                    $replica->submitted_time = now();
                }),
        ];
    }
}