<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainerResource\Pages;
use App\Models\Trainer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class TrainerResource extends Resource
{
    protected static ?string $model = Trainer::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Trainers';
    
    protected static ?string $navigationGroup = 'Course Management';
    
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('firstname')
                                    ->label('First Name')
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('surname')
                                    ->label('Surname')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('secondname')
                                    ->label('Second Name')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(255),
                            ]),
                        
                        Forms\Components\TextInput::make('trainer_title')
                            ->label('Title')
                            ->maxLength(255),
                        
                        Forms\Components\FileUpload::make('cv_path')
                            ->label('CV Upload')
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(5120) // 5MB
                            ->directory('trainer-cvs'),
                    ]),

                Forms\Components\Section::make('Professional Information')
                    ->schema([
                        Forms\Components\Textarea::make('academic_qualification')
                            ->label('Academic Qualification')
                            ->rows(3)
                            ->maxLength(500),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('specialization')
                                    ->label('Area of Specialization')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('experience')
                                    ->label('Experience')
                                    ->maxLength(255)
                                    ->helperText('e.g., "5 years in data analysis"'),
                            ]),
                        
                        Forms\Components\TextInput::make('subject')
                            ->label('Subject/Topic')
                            ->maxLength(255)
                            ->helperText('Main subject or topic of expertise'),
                    ]),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trainer_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['firstname', 'surname'])
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('specialization')
                    ->label('Specialization')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),
                
                Tables\Columns\TextColumn::make('experience')
                    ->label('Experience')
                    ->searchable()
                    ->limit(20),
                
                Tables\Columns\TextColumn::make('subject')
                    ->label('Subject')
                    ->searchable()
                    ->limit(20),
                
                Tables\Columns\IconColumn::make('cv_path')
                    ->label('CV')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                Tables\Columns\TextColumn::make('registered_time')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Availability filter removed - column does not exist in schema
                
                Tables\Filters\Filter::make('is_active')
                    ->label('Active Only')
                    ->query(fn (Builder $query): Builder => $query->active())
                    ->default(),
                
                Tables\Filters\Filter::make('available')
                    ->label('Available Only')
                    ->query(fn (Builder $query): Builder => $query->available()),
                
                Tables\Filters\Filter::make('experienced')
                    ->label('Experienced (5+ years)')
                    ->query(fn (Builder $query): Builder => $query->byExperience(5)),
                
                // High rated filter removed - rating is not a DB column
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('assign_course')
                    ->label('Assign to Course')
                    ->icon('heroicon-o-academic-cap')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->options(\App\Models\Course::active()->pluck('title', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options(\App\Models\TrainerAssignment::getRoles())
                            ->required()
                            ->default('Lead Trainer'),
                        Forms\Components\TextInput::make('rate')
                            ->label('Agreed Rate')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Assignment Notes')
                            ->rows(2),
                    ])
                    ->action(function (array $data, Trainer $record): void {
                        $course = \App\Models\Course::find($data['course_id']);
                        if ($course) {
                            $record->assignToCourse($course, [
                                'role' => $data['role'],
                                'rate_agreed' => $data['rate'] ?? $record->daily_rate,
                                'notes' => $data['notes'] ?? '',
                            ]);
                        }
                    }),
                
                // Update statistics action removed - methods do not exist
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->isSuperAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->isSuperAdmin()),
                    
                    // Bulk actions for is_active/availability removed - columns do not exist
                ]),
            ])
            ->defaultSort('trainer_id', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Trainer Profile')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\ImageEntry::make('profile_photo')
                                    ->label('Profile Photo')
                                    ->circular()
                                    ->size(150),
                                
                                Infolists\Components\Group::make([
                                    Infolists\Components\TextEntry::make('full_name')
                                        ->label('Full Name')
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->weight('bold'),
                                    
                                    Infolists\Components\TextEntry::make('email')
                                        ->label('Email')
                                        ->copyable(),
                                    
                                    Infolists\Components\TextEntry::make('phone')
                                        ->label('Phone')
                                        ->placeholder('Not provided'),
                                ])->columnSpan(2),
                            ]),
                        
                        Infolists\Components\TextEntry::make('bio')
                            ->label('Biography')
                            ->placeholder('No biography provided')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Professional Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('experience_years')
                                    ->label('Years of Experience')
                                    ->suffix(' years'),
                                
                                Infolists\Components\TextEntry::make('availability_status')
                                    ->label('Availability')
                                    ->badge(),
                            ]),
                        
                        Infolists\Components\TextEntry::make('specializations')
                            ->label('Specializations')
                            ->listWithLineBreaks()
                            ->placeholder('Not specified'),
                        
                        Infolists\Components\TextEntry::make('qualifications')
                            ->label('Qualifications')
                            ->listWithLineBreaks()
                            ->placeholder('Not specified'),
                        
                        Infolists\Components\TextEntry::make('certifications')
                            ->label('Certifications')
                            ->listWithLineBreaks()
                            ->placeholder('Not specified'),
                        
                        Infolists\Components\TextEntry::make('languages')
                            ->label('Languages')
                            ->listWithLineBreaks()
                            ->placeholder('Not specified'),
                    ]),

                Infolists\Components\Section::make('Statistics & Performance')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('rating')
                                    ->label('Rating')
                                    ->suffix('/5')
                                    ->badge(),
                                
                                Infolists\Components\TextEntry::make('total_courses_taught')
                                    ->label('Courses Taught')
                                    ->numeric(),
                                
                                Infolists\Components\TextEntry::make('total_students_trained')
                                    ->label('Students Trained')
                                    ->numeric(),
                                
                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('Active Status')
                                    ->boolean(),
                            ]),
                    ]),

                Infolists\Components\Section::make('Rates & Pricing')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('hourly_rate')
                                    ->label('Hourly Rate')
                                    ->money('USD'),
                                
                                Infolists\Components\TextEntry::make('daily_rate')
                                    ->label('Daily Rate')
                                    ->money('USD'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Contact Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('address')
                            ->label('Address')
                            ->placeholder('Not provided')
                            ->columnSpanFull(),
                        
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('city')
                                    ->label('City')
                                    ->placeholder('Not specified'),
                                
                                Infolists\Components\TextEntry::make('country')
                                    ->label('Country')
                                    ->placeholder('Not specified'),
                                
                                Infolists\Components\TextEntry::make('timezone')
                                    ->label('Timezone')
                                    ->placeholder('Not specified'),
                            ]),
                        
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('emergency_contact')
                                    ->label('Emergency Contact')
                                    ->placeholder('Not provided'),
                                
                                Infolists\Components\TextEntry::make('emergency_phone')
                                    ->label('Emergency Phone')
                                    ->placeholder('Not provided'),
                            ]),
                    ])
                    ->collapsed(),

                Infolists\Components\Section::make('Online Presence')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('linkedin_profile')
                                    ->label('LinkedIn Profile')
                                    ->url(fn ($state) => $state)
                                    ->placeholder('Not provided'),
                                
                                Infolists\Components\TextEntry::make('website')
                                    ->label('Personal Website')
                                    ->url(fn ($state) => $state)
                                    ->placeholder('Not provided'),
                            ]),
                    ])
                    ->collapsed(),

                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Internal Notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainers::route('/'),
            'create' => Pages\CreateTrainer::route('/create'),
            'view' => Pages\ViewTrainer::route('/{record}'),
            'edit' => Pages\EditTrainer::route('/{record}/edit'),
        ];
    }
}