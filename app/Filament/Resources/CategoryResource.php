<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Categories';
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('category_name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),

                // Image upload to the main site category images directory
                Forms\Components\FileUpload::make('category_image')
                    ->label('Category Image')
                    ->image()
                    ->disk('main_site_category')
                    ->directory('')
                    ->visibility('public')
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get): string {
                        $slug = \Illuminate\Support\Str::slug((string) $get('category_name'));
                        return "Training-courses-in-{$slug}-by-datastat-training-institute." . $file->getClientOriginalExtension();
                    })
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('Meta_Description')
                    ->rows(3)
                    ->label('Meta Description')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('Meta_Keywords')
                    ->rows(2)
                    ->label('Meta Keywords')
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('category_image')
                    ->label('Image')
                    ->disk('main_site_category')
                    ->square()
                    ->height(48),
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('category_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('Meta_Description')->limit(50),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->isSuperAdmin()),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}

