<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsResource\Pages;
use App\Models\News;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Str;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    
    protected static ?string $navigationLabel = 'News & Blog';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Article Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Article Title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $context, $state, Forms\Set $set) => 
                                $context === 'create' ? $set('slug', Str::slug($state)) : null
                            ),
                        
                        // Slug removed - not stored in DB
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('category')
                                    ->label('Category')
                                    ->options(News::getCategories())
                                    ->required()
                                    ->searchable(),
                                
                                // Author removed - not stored in DB
                            ]),
                    ]),

                Forms\Components\Section::make('Content')
                    ->schema([
                        Forms\Components\Textarea::make('introduction')
                            ->label('Article Introduction/Summary')
                            ->maxLength(1000)
                            ->rows(3)
                            ->helperText('Brief summary that will appear in article previews'),
                        
                        Forms\Components\RichEditor::make('body')
                            ->label('Article Content')
                            ->required()
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ]),
                    ]),

                Forms\Components\Section::make('Featured Image')
                    ->schema([
                        // Using legacy `photo` column only
                        Forms\Components\TextInput::make('photo')
                            ->label('Image filename in uploads/')
                            ->helperText('Place the image under public/uploads and enter the filename, e.g. my-image.jpg')
                            ->columnSpanFull(),
                        
                        Forms\Components\Placeholder::make('preview')
                            ->label('Preview')
                            ->content(fn (?News $record) => $record?->featured_image_url ?? ''),
                    ]),

                // SEO & Publishing section removed - not stored in DB
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\ImageColumn::make('featured_image_url')
                    ->label('Image')
                    ->size(60)
                    ->square(),
                
                Tables\Columns\TextColumn::make('title')
                    ->label('Article Title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 40) {
                            return null;
                        }
                        return $state;
                    }),
                
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                
                
                // Published flag removed - not stored in DB
                
                // Featured flag removed - not stored in DB
                
                Tables\Columns\TextColumn::make('reading_time')
                    ->label('Read Time')
                    ->suffix(' min')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options(News::getCategories()),

                Tables\Filters\Filter::make('recent')
                    ->label('Recent Articles')
                    ->query(fn (Builder $query): Builder => $query->where('time', '>=', now()->subDays(30))),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('View & Edit'),
                Tables\Actions\ReplicateAction::make()
                    ->beforeReplicaSaved(function (News $replica): void {
                        $replica->title = $replica->title . ' (Copy)';
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->isSuperAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->isSuperAdmin()),
                ]),
            ])
            ->defaultSort('id', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Article Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('title')
                                    ->label('Title')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->columnSpanFull(),
                                
                                Infolists\Components\TextEntry::make('category')
                                    ->label('Category')
                                    ->badge(),
                                
                                Infolists\Components\TextEntry::make('author')
                                    ->label('Author'),
                            ]),
                        
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('time')
                                    ->label('Timestamp'),
                                Infolists\Components\TextEntry::make('reading_time')
                                    ->label('Reading Time')
                                    ->suffix(' minutes'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Content')
                    ->schema([
                        Infolists\Components\TextEntry::make('introduction')
                            ->label('Introduction')
                            ->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('body')
                            ->label('Article Content')
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Featured Image')
                    ->schema([
                        Infolists\Components\ImageEntry::make('featured_image_url')
                            ->label('Featured Image')
                            ->size(400),
                    ])
                    ->collapsed(),

                // SEO & Publishing details removed - fields not in DB
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
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'view' => Pages\ViewNews::route('/{record}'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
        ];
    }
}