<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegistrantResource\Pages;
use App\Models\Registrant;
use App\Models\Course;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class RegistrantResource extends Resource
{
    protected static ?string $model = Registrant::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Registrants';
    
    protected static ?string $navigationGroup = 'Course Management';
    
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Course & Registration')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('course_id')
                                    ->label('Course')
                                    ->options(Course::active()->pluck('title', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                
                                Forms\Components\TextInput::make('registered_time')
                                    ->label('Registration Time')
                                    ->default(now()->format('Y-m-d H:i:s')),
                            ]),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('how_you_heard')
                                    ->label('How You Heard About Us')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('learning_model')
                                    ->label('Learning Model')
                                    ->maxLength(100),
                            ]),
                    ]),

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
                                
                                Forms\Components\TextInput::make('personal_email')
                                    ->label('Personal Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(255),
                            ]),
                        
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('registrant_title')
                                    ->label('Title')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('organization')
                                    ->label('Organization')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('country')
                                    ->label('Country')
                                    ->maxLength(255),
                            ]),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('academic_qualification')
                                    ->label('Academic Qualification')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('department')
                                    ->label('Department')
                                    ->maxLength(255),
                            ]),
                    ]),

                Forms\Components\Section::make('Payment & Course Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('mode_of_payment')
                                    ->label('Mode of Payment')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total Amount')
                                    ->numeric()
                                    ->prefix('$'),
                                
                                Forms\Components\TextInput::make('invoice_no')
                                    ->label('Invoice Number')
                                    ->maxLength(255),
                            ]),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title_course')
                                    ->label('Course Title')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('registrant_no')
                                    ->label('Registrant Number')
                                    ->maxLength(255),
                            ]),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('accommodation')
                                    ->label('Accommodation Needed')
                                    ->options([
                                        'Yes' => 'Yes',
                                        'No' => 'No',
                                    ])
                                    ->default('No'),
                                
                                Forms\Components\Select::make('airport_pickup')
                                    ->label('Airport Pickup Needed')
                                    ->options([
                                        'Yes' => 'Yes',
                                        'No' => 'No',
                                    ])
                                    ->default('No'),
                            ]),
                    ]),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('registrants_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['firstname', 'surname'])
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('surname', $direction)->orderBy('firstname', $direction);
                    })
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('personal_email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('title_course')
                    ->label('Course')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),
                

                
                Tables\Columns\TextColumn::make('country')
                    ->label('Country')
                    ->searchable()
                    ->limit(15),
                
                Tables\Columns\TextColumn::make('registered_time')
                    ->label('Registered')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Course')
                    ->options(Course::active()->pluck('title', 'id'))
                    ->searchable(),
                
                // Payment status filter retained via accessor - works without DB column
                
                // Attendance status filter removed - not stored in DB
                
                // Completion status filter removed - not stored in DB
                
                // Certificate issued filter removed - not stored in DB
                
                Tables\Filters\Filter::make('recent')
                    ->label('Recent (Last 30 Days)')
                    ->query(fn (Builder $query): Builder => $query->recent(30)),
                
                // Upcoming courses filter removed - no supporting scope/columns
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount Paid')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('method')
                            ->label('Payment Method')
                            ->options(Registrant::getPaymentMethods())
                            ->required(),
                        Forms\Components\TextInput::make('reference')
                            ->label('Payment Reference'),
                    ])
                    ->action(function (array $data, Registrant $record): void {
                        $record->markAsPaid($data['amount'], $data['method'], $data['reference']);
                    })
                    ->visible(fn (Registrant $record): bool => $record->payment_status !== 'Paid'),
                
                Tables\Actions\Action::make('mark_present')
                    ->label('Mark Present')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Registrant $record) => $record->markAsPresent())
                    ->visible(fn (Registrant $record): bool => $record->attendance_status !== 'Present'),
                
                Tables\Actions\Action::make('mark_completed')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Registrant $record) => $record->markAsCompleted())
                    ->visible(fn (Registrant $record): bool => $record->completion_status !== 'Completed'),
                
                Tables\Actions\Action::make('issue_certificate')
                    ->label('Issue Certificate')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(fn (Registrant $record) => $record->issueCertificate())
                    ->visible(fn (Registrant $record): bool => 
                        $record->completion_status === 'Completed' && !$record->certificate_issued
                    ),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->isSuperAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->isSuperAdmin()),
                    
                    Tables\Actions\BulkAction::make('mark_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('Amount Paid')
                                ->numeric()
                                ->required(),
                            Forms\Components\Select::make('method')
                                ->label('Payment Method')
                                ->options(Registrant::getPaymentMethods())
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $records->each(fn($record) => $record->markAsPaid($data['amount'], $data['method']));
                        }),
                    
                    Tables\Actions\BulkAction::make('send_reminders')
                        ->label('Send Reminders')
                        ->icon('heroicon-o-bell')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each(fn($record) => $record->sendReminder());
                        }),
                    
                    Tables\Actions\BulkAction::make('issue_certificates')
                        ->label('Issue Certificates')
                        ->icon('heroicon-o-academic-cap')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function($record) {
                                if ($record->completion_status === 'Completed') {
                                    $record->issueCertificate();
                                }
                            });
                        }),
                ]),
            ])
            ->defaultSort('registrants_id', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Registration Information')
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
                                Infolists\Components\TextEntry::make('course.title')
                                    ->label('Course'),
                                
                                Infolists\Components\TextEntry::make('registration_date')
                                    ->label('Registration Date')
                            ]),
                    ]),

                Infolists\Components\Section::make('Contact Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('phone')
                                    ->label('Phone Number')
                                    ->placeholder('Not provided'),
                                
                                Infolists\Components\TextEntry::make('organization')
                                    ->label('Organization')
                                    ->placeholder('Not specified'),
                            ]),
                        
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('position')
                                    ->label('Position')
                                    ->placeholder('Not specified'),
                                
                                Infolists\Components\TextEntry::make('registration_source')
                                    ->label('Registration Source')
                                    ->badge(),
                            ]),
                    ]),

                Infolists\Components\Section::make('Payment Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('payment_status')
                                    ->label('Payment Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Paid' => 'success',
                                        'Pending' => 'warning',
                                        'Failed' => 'danger',
                                        'Refunded' => 'gray',
                                        default => 'gray',
                                    }),
                                
                                Infolists\Components\TextEntry::make('payment_method')
                                    ->label('Payment Method')
                                    ->placeholder('Not specified'),
                                
                                Infolists\Components\TextEntry::make('final_amount')
                                    ->label('Final Amount')
                                    ->money('USD'),
                            ]),
                        
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('payment_reference')
                                    ->label('Payment Reference')
                                    ->placeholder('Not provided'),
                                
                                Infolists\Components\TextEntry::make('accommodation')
                                    ->label('Accommodation Needed')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Yes' => 'success',
                                        'No' => 'gray',
                                        default => 'gray',
                                    }),
                                
                                Infolists\Components\TextEntry::make('airport_pickup')
                                    ->label('Airport Pickup Needed')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Yes' => 'success',
                                        'No' => 'gray',
                                        default => 'gray',
                                    }),
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
            'index' => Pages\ListRegistrants::route('/'),
            'create' => Pages\CreateRegistrant::route('/create'),
            'view' => Pages\ViewRegistrant::route('/{record}'),
            'edit' => Pages\EditRegistrant::route('/{record}/edit'),
        ];
    }
}