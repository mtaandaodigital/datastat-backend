<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Leads';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(50),
                            ]),
                        

                    ]),

                Forms\Components\Section::make('Lead Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options(Lead::getStatuses())
                                    ->required()
                                    ->default('New'),

                                Forms\Components\Select::make('source')
                                    ->label('Lead Source')
                                    ->options(Lead::getSources())
                                    ->required()
                                    ->default('Website'),
                            ]),
                        
                        Forms\Components\TextInput::make('interest')
                            ->label('Interest/Course of Interest')
                            ->maxLength(255)
                            ->helperText('What course or service is the lead interested in?'),
                        

                    ]),



                Forms\Components\Section::make('Notes & Comments')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(5),
                    ]),
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
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->placeholder('Not provided'),
                

                
                Tables\Columns\TextColumn::make('interest')
                    ->label('Interest')
                    ->limit(30)
                    ->placeholder('Not specified'),
                
                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Landing Page' => 'success',
                        'Website' => 'primary',
                        'Referral' => 'warning',
                        'Social Media' => 'info',
                        'Email Campaign' => 'purple',
                        'Other' => 'gray',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'New' => 'primary',
                        'Contacted' => 'info',
                        'Qualified' => 'warning',
                        'Converted' => 'success',
                        'Lost' => 'danger',
                        default => 'gray',
                    }),
                

                

                

                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Lead::getStatuses()),
                
                Tables\Filters\SelectFilter::make('source')
                    ->label('Source')
                    ->options(Lead::getSources()),
                

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('contact')
                    ->label('Mark Contacted')
                    ->icon('heroicon-o-phone')
                    ->color('info')
                    ->action(fn (Lead $record) => $record->markAsContacted())
                    ->visible(fn (Lead $record): bool => $record->status !== 'Contacted'),

                Tables\Actions\Action::make('convert')
                    ->label('Mark Converted')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Lead $record) => $record->markAsConverted())
                    ->visible(fn (Lead $record): bool => $record->status !== 'Converted'),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->isSuperAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->isSuperAdmin()),
                    

                    
                    Tables\Actions\BulkAction::make('update_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options(Lead::getStatuses())
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $records->each(fn($record) => $record->update(['status' => $data['status']]));
                        }),
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
                Infolists\Components\Section::make('Contact Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Full Name')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                                
                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email Address')
                                    ->copyable(),
                            ]),
                        
                        Infolists\Components\Grid::make(1)
                            ->schema([
                                Infolists\Components\TextEntry::make('phone')
                                    ->label('Phone Number')
                                    ->placeholder('Not provided'),
                            ]),
                        

                    ]),

                Infolists\Components\Section::make('Lead Details')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'New' => 'primary',
                                        'Contacted' => 'info',
                                        'Qualified' => 'warning',
                                        'Converted' => 'success',
                                        'Lost' => 'danger',
                                        default => 'gray',
                                    }),
                                
                                Infolists\Components\TextEntry::make('source')
                                    ->label('Source')
                                    ->badge(),
                                

                            ]),
                        
                        Infolists\Components\TextEntry::make('interest')
                            ->label('Interest')
                            ->placeholder('Not specified'),
                        

                    ]),

                Infolists\Components\Section::make('Timeline')
                    ->schema([
                        Infolists\Components\Grid::make(1)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime(),
                            ]),
                    ]),

                Infolists\Components\Section::make('Notes & Comments')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->placeholder('No notes added')
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
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->isSuperAdmin();
    }
}