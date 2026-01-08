<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';
    
    protected static ?string $title = 'All Course Schedules';
    
    protected static ?string $label = 'Schedule';
    
    protected static ?string $pluralLabel = 'Schedules';
    
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('start')->required()->format('Y-m-d'),
                Forms\Components\DatePicker::make('end')->required()->after('start')->format('Y-m-d'),
                Forms\Components\TextInput::make('location')->required()->maxLength(255),
                Forms\Components\TextInput::make('course_fee_usd')->numeric()->required()->prefix('$'),
                Forms\Components\TextInput::make('course_fee_ksh')->numeric()->required()->prefix('KES'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('start')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end')
                    ->label('End Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course_fee_usd')
                    ->label('Price (USD)')
                    ->money('usd', true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('course_fee_ksh')
                    ->label('Price (KES)')
                    ->formatStateUsing(fn ($state) => $state ? 'KES ' . number_format($state) : '-')
                    ->sortable(),
            ])
            ->defaultSort('start_data', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

