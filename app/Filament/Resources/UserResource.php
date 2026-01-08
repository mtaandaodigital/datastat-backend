<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Users';
    
    protected static ?int $navigationSort = 2;

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
                                    ->label('Last Name')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('telephone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(20),

                                Forms\Components\TextInput::make('location')
                                    ->label('Organization')
                                    ->maxLength(255),
                            ]),
                    ]),

                Forms\Components\Section::make('Account Security')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(6)
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn ($state) => md5($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->helperText(fn (string $context): string =>
                                $context === 'edit' ? 'Leave blank to keep current password' : 'Minimum 6 characters'
                            ),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->same('password')
                            ->dehydrated(false),
                    ])
                    ->visible(fn () => auth()->user()->isSuperAdmin()),

                Forms\Components\Section::make('Permissions & Access')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('login_active_status')
                                    ->label('Account Status')
                                    ->options([
                                        0 => 'Inactive',
                                        1 => 'Active',
                                    ])
                                    ->required()
                                    ->default(1),
                                
                                Forms\Components\Select::make('accesslevel')
                                    ->label('Access Level')
                                    ->options([
                                        0 => 'No Access',
                                        1 => 'Full Access',
                                    ])
                                    ->required()
                                    ->default(1),
                                
                                Forms\Components\Select::make('user_level')
                                    ->label('User Level')
                                    ->options([
                                        0 => 'Regular User',
                                        1 => 'Super Admin',
                                    ])
                                    ->required()
                                    ->default(0),
                            ]),
                    ])
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('usermanagementid')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable(['firstname', 'surname'])
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('organization')
                    ->label('Organization')
                    ->searchable(['location'])
                    ->limit(30)
                    ->placeholder('Not specified'),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->placeholder('Not provided'),
                
                Tables\Columns\IconColumn::make('login_active_status')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                Tables\Columns\TextColumn::make('accesslevel')
                    ->label('Access Level')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '0' => 'No Access',
                        '1' => 'Full Access',
                        default => 'Unknown',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '0' => 'danger',
                        '1' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('user_level')
                    ->label('User Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '0' => 'Regular User',
                        '1' => 'Super Admin',
                        default => 'Unknown',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '0' => 'primary',
                        '1' => 'warning',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('login_active_status')
                    ->label('Status')
                    ->options([
                        '0' => 'Inactive',
                        '1' => 'Active',
                    ]),
                
                Tables\Filters\SelectFilter::make('accesslevel')
                    ->label('Access Level')
                    ->options([
                        '0' => 'No Access',
                        '1' => 'Full Access',
                    ]),
                
                Tables\Filters\SelectFilter::make('user_level')
                    ->label('User Type')
                    ->options([
                        '0' => 'Regular User',
                        '1' => 'Super Admin',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('toggle_status')
                    ->label('Toggle Status')
                    ->icon(fn (User $record): string => 
                        $record->login_active_status ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle'
                    )
                    ->color(fn (User $record): string => 
                        $record->login_active_status ? 'danger' : 'success'
                    )
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $newActive = !$record->login_active_status;
                        $updates = [
                            'login_active_status' => $newActive ? 1 : 0,
                            'activation_status' => $newActive ? 1 : 0,
                        ];
                        // When activating, ensure accesslevel is Full Access (1). user_level remains unchanged.
                        if ($newActive) {
                            $updates['accesslevel'] = 1;
                        }
                        $record->update($updates);
                        \Filament\Notifications\Notification::make()
                            ->title('User status updated')
                            ->body('Active: ' . ($record->login_active_status ? '1' : '0') . ", Activation: " . ($record->activation_status ?? 'null') . ", Access: " . ($record->accesslevel ?? 'null'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record) => 
                        auth()->user()->isSuperAdmin() && 
                        $record->usermanagementid !== auth()->user()->usermanagementid
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->isSuperAdmin()),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function($record) {
                                $record->update([
                                    'login_active_status' => 1,
                                    'activation_status' => 1,
                                    'accesslevel' => 1, // Ensure panel access
                                ]);
                            });
                        })
                        ->visible(fn () => auth()->user()->isSuperAdmin()),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function($record) {
                                $record->update([
                                    'login_active_status' => 0,
                                    'activation_status' => 0,
                                ]);
                            });
                        })
                        ->visible(fn () => auth()->user()->isSuperAdmin()),
                ]),
            ])
            ->defaultSort('usermanagementid', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Personal Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('full_name')
                                    ->label('Full Name')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                                
                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email Address')
                                    ->copyable(),
                            ]),
                        
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('phone')
                                    ->label('Phone Number')
                                    ->placeholder('Not provided'),
                                
                                Infolists\Components\TextEntry::make('organization')
                                    ->label('Organization')
                                    ->placeholder('Not specified'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Account Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('login_active_status')
                                    ->label('Account Status')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        '0' => 'Inactive',
                                        '1' => 'Active',
                                        default => 'Unknown',
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        '0' => 'danger',
                                        '1' => 'success',
                                        default => 'gray',
                                    }),
                                
                                Infolists\Components\TextEntry::make('accesslevel')
                                    ->label('Access Level')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        '0' => 'No Access',
                                        '1' => 'Full Access',
                                        default => 'Unknown',
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        '0' => 'danger',
                                        '1' => 'success',
                                        default => 'gray',
                                    }),
                                
                                Infolists\Components\TextEntry::make('user_level')
                                    ->label('User Type')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        '0' => 'Regular User',
                                        '1' => 'Super Admin',
                                        default => 'Unknown',
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        '0' => 'primary',
                                        '1' => 'warning',
                                        default => 'gray',
                                    }),
                            ]),
                        
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Account Created')
                                    ->dateTime(),
                                
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime(),
                            ]),
                    ]),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->isSuperAdmin();
    }
}