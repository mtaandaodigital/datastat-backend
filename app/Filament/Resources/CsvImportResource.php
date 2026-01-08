<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CsvImportResource\Pages;
use App\Models\CsvImport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Illuminate\Support\Facades\Schema;
use Filament\Infolists\Infolist;

class CsvImportResource extends Resource
{
    protected static ?string $model = CsvImport::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    
    protected static ?string $navigationLabel = 'CSV Imports';

    public static function shouldRegisterNavigation(): bool
    {
        // Hide if the csv_imports table doesn't exist; imports are file-based for now
        return \Illuminate\Support\Facades\Schema::hasTable('csv_imports') && auth()->check() && auth()->user()->isSuperAdmin();
    }
    
    protected static ?string $navigationGroup = 'Data Management';
    
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Import Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('original_filename')
                                    ->label('Original Filename')
                                    ->disabled(),
                                
                                Forms\Components\Select::make('type')
                                    ->label('Import Type')
                                    ->options(CsvImport::getTypes())
                                    ->disabled(),
                            ]),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('status')
                                    ->label('Status')
                                    ->disabled(),
                                
                                Forms\Components\TextInput::make('user.full_name')
                                    ->label('Imported By')
                                    ->disabled(),
                            ]),
                    ]),

                Forms\Components\Section::make('Progress Statistics')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('total_rows')
                                    ->label('Total Rows')
                                    ->disabled(),
                                
                                Forms\Components\TextInput::make('processed_rows')
                                    ->label('Processed')
                                    ->disabled(),
                                
                                Forms\Components\TextInput::make('successful_rows')
                                    ->label('Successful')
                                    ->disabled(),
                                
                                Forms\Components\TextInput::make('failed_rows')
                                    ->label('Failed')
                                    ->disabled(),
                            ]),
                    ]),

                Forms\Components\Section::make('Timeline')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DateTimePicker::make('created_at')
                                    ->label('Created At')
                                    ->disabled(),
                                
                                Forms\Components\DateTimePicker::make('started_at')
                                    ->label('Started At')
                                    ->disabled(),
                                
                                Forms\Components\DateTimePicker::make('completed_at')
                                    ->label('Completed At')
                                    ->disabled(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('original_filename')
                    ->label('Filename')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'courses' => 'success',
                        'users' => 'info',
                        'leads' => 'warning',
                        'news' => 'primary',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->suffix('%')
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 100 => 'success',
                        $state >= 50 => 'warning',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('total_rows')
                    ->label('Total Rows')
                    ->numeric(),
                
                Tables\Columns\TextColumn::make('successful_rows')
                    ->label('Success')
                    ->numeric()
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('failed_rows')
                    ->label('Failed')
                    ->numeric()
                    ->color('danger'),
                
                Tables\Columns\TextColumn::make('success_rate')
                    ->label('Success Rate')
                    ->suffix('%')
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 70 => 'warning',
                        default => 'danger',
                    }),
                
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Imported By')
                    ->placeholder('Unknown'),
                
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->placeholder('Not completed'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Import Type')
                    ->options(CsvImport::getTypes()),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(CsvImport::getStatuses()),
                
                Tables\Filters\Filter::make('recent')
                    ->label('Recent (Last 30 Days)')
                    ->query(fn (Builder $query): Builder => $query->recent(30)),
                
                Tables\Filters\Filter::make('failed_only')
                    ->label('Failed Imports')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'failed')),
                
                Tables\Filters\Filter::make('completed_only')
                    ->label('Completed Imports')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'completed')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\Action::make('download_errors')
                    ->label('Download Errors')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->action(function (CsvImport $record) {
                        if (!$record->errors || empty($record->errors)) {
                            return;
                        }
                        
                        $filename = 'import_errors_' . $record->id . '.json';
                        $content = json_encode($record->errors, JSON_PRETTY_PRINT);
                        
                        return response()->streamDownload(function () use ($content) {
                            echo $content;
                        }, $filename, [
                            'Content-Type' => 'application/json',
                        ]);
                    })
                    ->visible(fn (CsvImport $record): bool => 
                        $record->status === 'failed' && !empty($record->errors)
                    ),
                
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
                Infolists\Components\Section::make('Import Details')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('original_filename')
                                    ->label('Original Filename')
                                    ->copyable(),
                                
                                Infolists\Components\TextEntry::make('type')
                                    ->label('Import Type')
                                    ->badge(),
                            ]),
                        
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'gray',
                                        'processing' => 'warning',
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    }),
                                
                                Infolists\Components\TextEntry::make('user.full_name')
                                    ->label('Imported By')
                                    ->placeholder('Unknown'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Progress Statistics')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_rows')
                                    ->label('Total Rows')
                                    ->numeric(),
                                
                                Infolists\Components\TextEntry::make('processed_rows')
                                    ->label('Processed')
                                    ->numeric(),
                                
                                Infolists\Components\TextEntry::make('successful_rows')
                                    ->label('Successful')
                                    ->numeric()
                                    ->color('success'),
                                
                                Infolists\Components\TextEntry::make('failed_rows')
                                    ->label('Failed')
                                    ->numeric()
                                    ->color('danger'),
                            ]),
                        
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('progress_percentage')
                                    ->label('Progress')
                                    ->suffix('%')
                                    ->badge(),
                                
                                Infolists\Components\TextEntry::make('success_rate')
                                    ->label('Success Rate')
                                    ->suffix('%')
                                    ->badge(),
                            ]),
                    ]),

                Infolists\Components\Section::make('Timeline')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime(),
                                
                                Infolists\Components\TextEntry::make('started_at')
                                    ->label('Started At')
                                    ->dateTime()
                                    ->placeholder('Not started'),
                                
                                Infolists\Components\TextEntry::make('completed_at')
                                    ->label('Completed At')
                                    ->dateTime()
                                    ->placeholder('Not completed'),
                            ]),
                        
                        Infolists\Components\TextEntry::make('duration')
                            ->label('Duration')
                            ->placeholder('Not completed'),
                    ]),

                Infolists\Components\Section::make('Errors')
                    ->schema([
                        Infolists\Components\TextEntry::make('errors')
                            ->label('Error Details')
                            ->formatStateUsing(function ($state) {
                                if (!$state || empty($state)) {
                                    return 'No errors';
                                }
                                
                                $errorText = '';
                                foreach ($state as $error) {
                                    $errorText .= "Row {$error['row']}: {$error['error']}\n";
                                }
                                
                                return $errorText;
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record): bool => !empty($record->errors))
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
            'index' => Pages\ListCsvImports::route('/'),
            'view' => Pages\ViewCsvImport::route('/{record}'),
            'import' => Pages\ImportCsv::route('/import'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Use custom import page instead
    }
}