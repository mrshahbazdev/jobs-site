<?php

namespace App\Filament\Widgets;

use App\Models\JobListing;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestJobsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(JobListing::query()->latest()->limit(5))
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Category'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Added At'),
                ToggleColumn::make('is_active')
                    ->label('Live'),
            ])
            ->actions([
                Action::make('view')
                    ->url(fn (JobListing $record): string => url('/jobs/'.$record->slug))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-eye'),
            ]);
    }
}
