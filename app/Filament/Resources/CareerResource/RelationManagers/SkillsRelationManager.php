<?php

namespace App\Filament\Resources\CareerResource\RelationManagers;

use App\Models\CourseEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Actions\Action;

class SkillsRelationManager extends RelationManager
{
    protected static string $relationship = 'skills';

    protected static ?string $recordTitleAttribute = 'skill';

    public function form(Form $form): Form
    {
        // Get unique software values from course_event table
        $availableSkills = CourseEvent::distinct()
            ->orderBy('software')
            ->pluck('software')
            ->filter()
            ->toArray();

        return $form
            ->schema([
                Forms\Components\Select::make('skill')
                    ->options(array_combine($availableSkills, $availableSkills))
                    ->required()
                    ->searchable()
                    ->unique(ignoreRecord: true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('skill')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('courses_count')
                    ->label('Related Courses')
                    ->getStateUsing(function ($record) {
                        return CourseEvent::where('software', $record->skill)->count();
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalWidth('lg'),
            ])
            ->actions([
                Action::make('viewCourses')
                    ->label('View Related Courses')
                    ->icon('heroicon-o-book-open')
                    ->modalContent(function ($record) {
                        $courses = CourseEvent::where('software', $record->skill)
                            ->get()
                            ->map(function ($course) {
                                return "• {$course->title} (Start: " . ($course->start_date ?? 'TBA') . ")";
                            })
                            ->join("\n");
                        
                        return view('filament::components.modal.description', [
                            'content' => "### Courses using {$record->skill}\n\n{$courses}"
                        ]);
                    })
                    ->modalWidth('lg'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}