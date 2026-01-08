<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use App\Enums\EmailTemplateType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\TernaryFilter;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    // Place email templates under a clear Communications area in the Filament sidebar
    protected static ?string $navigationGroup = 'Communications';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) EmailTemplate::where('is_active', true)->count();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Email Template Details')
                    ->description('Configure email templates for training notifications')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Template Type')
                            ->options(collect(EmailTemplateType::cases())->mapWithKeys(function ($case) {
                                return [$case->value => $case->getLabel() . ' - ' . $case->getDescription()];
                            })->toArray())
                            ->enum(EmailTemplateType::class)
                            ->required()
                            ->disabled(fn (?EmailTemplate $record) => $record !== null) // Prevent editing type after creation
                            ->helperText('Select the type of email template. This cannot be changed after creation.'),

                        Forms\Components\TextInput::make('subject')
                            ->label('Email Subject')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Use placeholders like {{participant_name}}, {{training_date}}, {{course_title}}, etc.'),

                        Forms\Components\RichEditor::make('body_html')
                            ->label('Email Body (HTML)')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->helperText('Use the following placeholders:
                                <ul>
                                    <li><code>{{participant_name}}</code> - Participant full name</li>
                                    <li><code>{{training_date}}</code> - Full training date range (e.g., Jan 01, 2025 – Jan 05, 2025)</li>
                                    <li><code>{{start_date}}</code> - Start date only</li>
                                    <li><code>{{end_date}}</code> - End date only</li>
                                    <li><code>{{course_title}}</code> - Course/program title</li>
                                    <li><code>{{training_location}}</code> - Training location</li>
                                    <li><code>{{coordinator_name}}</code> - Coordinator name (defaults to "Training Coordinator")</li>
                                </ul>'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive templates will not be used for sending emails'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Template Type')
                    ->formatStateUsing(function (EmailTemplateType $state) {
                        return $state->getLabel();
                    })
                    ->searchable(),

                TextColumn::make('subject')
                    ->label('Subject')
                    ->limit(50)
                    ->searchable(),

                BooleanColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (EmailTemplate $record) {
                        // Optionally, prevent deletion of seeded templates
                        if (in_array($record->type->value, ['registration_confirmation', 'attendance_confirmation', 'final_preparation'])) {
                            // Allow deletion but could add a warning
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50]);
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
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
