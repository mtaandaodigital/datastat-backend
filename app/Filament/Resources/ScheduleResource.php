<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Schedule;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Hide global schedules from sidebar; manage within Course only
    }


    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Schedules';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->label('Course')
                    ->options(Course::query()->pluck('title', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\DatePicker::make('start')->required()->format('Y-m-d'),
                Forms\Components\DatePicker::make('end')->required()->after('start')->format('Y-m-d'),
                Forms\Components\TextInput::make('location')->required()->maxLength(255),
                Forms\Components\TextInput::make('course_fee_usd')->numeric()->required()->prefix('$'),
                Forms\Components\TextInput::make('course_fee_ksh')->numeric()->required()->prefix('KES'),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('schedule_id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('course.title')->label('Course')->searchable(),
                Tables\Columns\TextColumn::make('start')->date()->sortable(),
                Tables\Columns\TextColumn::make('end')->date()->sortable(),
                Tables\Columns\TextColumn::make('location')->searchable(),
                Tables\Columns\TextColumn::make('course_fee_usd')->label('USD')->money('usd', true),
                Tables\Columns\TextColumn::make('course_fee_ksh')->label('KES')->sortable(),
            ])
            ->filters([])
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}

