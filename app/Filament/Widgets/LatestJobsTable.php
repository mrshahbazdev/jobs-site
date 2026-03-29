<?php

namespace App\Filament\Widgets;

use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use App\Models\JobListing;

class LatestJobsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(\App\Models\JobListing::query()->latest()->limit(5))
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('category.name')
                    ->label('Category'),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Added At'),
                \Filament\Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Live'),
            ])
            ->actions([
                Action::make('view')
                    ->url(fn (\App\Models\JobListing $record): string => url('/jobs/' . $record->slug))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-eye'),
            ]);
    }
}
