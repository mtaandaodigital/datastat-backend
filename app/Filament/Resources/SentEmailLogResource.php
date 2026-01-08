<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SentEmailLogResource\Pages;
use App\Models\SentEmailLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\ExportAction;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry as InfolistTextEntry;
use Filament\Forms;

class SentEmailLogResource extends Resource
{
    protected static ?string $model = SentEmailLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationGroup = 'Communications';
    protected static ?int $navigationSort = 6;

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('subject')->label('Subject')->limit(60)->wrap()->searchable(),
                TextColumn::make('template.subject')->label('Template')->limit(36)->wrap()->toggleable(),
                TextColumn::make('registrant.full_name')->label('Registrant')->limit(36)->wrap()->searchable(),
                TextColumn::make('registrant.personal_email')->label('Email')->limit(40)->copyable(),
                TextColumn::make('admin.name')->label('Sent By')->default('System')->limit(30),
                TextColumn::make('created_at')->label('Sent At')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('admin_id')
                    ->label('Sent By')
                    ->options(fn () => \App\Models\User::query()->get()
                        ->mapWithKeys(fn ($u) => [
                            $u->usermanagementid => $u->name,
                        ])->toArray()
                    ),

                Filter::make('sent_date')
                    ->label('Sent Date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('From'),
                        Forms\Components\DatePicker::make('created_until')->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                            ->when($data['created_until'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->url(fn (SentEmailLog $record) => route('admin.sent_email_logs.preview', ['id' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([20])
            ->headerActions([
                // Header actions must be Filament\Actions\Action instances; keep empty or provide Actions\Action
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Details')
                    ->schema([
                        InfolistTextEntry::make('subject')->label('Subject'),
                        InfolistTextEntry::make('admin.name')->label('Sent By')->default('System'),
                        InfolistTextEntry::make('registrant.full_name')->label('Registrant'),
                        InfolistTextEntry::make('registrant.personal_email')->label('Email'),
                        InfolistTextEntry::make('created_at')->label('Sent At')->dateTime(),
                    ]),

                Infolists\Components\Section::make('Body')
                    ->schema([
                        InfolistTextEntry::make('body_html')
                            ->label('Body (HTML)')
                            ->html()
                            ->prose()
                            ->limit(0),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSentEmailLogs::route('/'),
            'view' => Pages\ViewSentEmailLog::route('/{record}'),
        ];
    }
}
