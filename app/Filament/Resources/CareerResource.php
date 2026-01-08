<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareerResource\Pages;
use App\Models\Career;
use App\Models\CourseEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Str;
use Filament\Tables\Actions\Action;

class CareerResource extends Resource
{
    protected static ?string $model = Career::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Career Management';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationLabel(): string
    {
        return 'Career Paths';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Career Paths';
    }

    public static function getModelLabel(): string
    {
        return 'Career Path';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\FileUpload::make('image_path')
                            ->image()
                            ->directory('careers'),
                        
                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->columnSpan('full'),
                        
                        Forms\Components\Textarea::make('meta_description')
                            ->rows(3)
                            ->columnSpan('full'),
                        
                        Forms\Components\Textarea::make('meta_keywords')
                            ->rows(3)
                            ->columnSpan('full'),

                        // Add skills select for quick adding during career creation/editing
                        Forms\Components\Select::make('skills')
                            ->multiple()
                            ->label('Add Skills')
                            ->options(
                                CourseEvent::distinct()
                                    ->pluck('software', 'software')
                                    ->filter()
                            )
                            ->columnSpan('full')
                            ->dehydrated(false) // This prevents the field from being included in the form data
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record && $state) {
                                    foreach ($state as $skill) {
                                        $record->skills()->firstOrCreate(['skill' => $skill]);
                                    }
                                }
                            }),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('image_path')
                    ->circular(),
                Tables\Columns\TextColumn::make('skills_count')
                    ->counts('skills')
                    ->label('Skills'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Action::make('viewCourses')
                    ->label('View Courses')
                    ->icon('heroicon-o-book-open')
                    ->url(fn (Career $record) => static::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            CareerResource\RelationManagers\SkillsRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCareers::route('/'),
            'create' => Pages\CreateCareer::route('/create'),
            'edit' => Pages\EditCareer::route('/{record}/edit'),
            'view' => Pages\ViewCareer::route('/{record}'),
        ];
    }    
}