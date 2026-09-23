<?php

namespace App\Filament\Resources\JobListings\Tables;

use App\Models\JobListing;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class JobListingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                TextColumn::make('city.name')
                    ->label('City')
                    ->sortable(),

                TextColumn::make('deadline')
                    ->date()
                    ->sortable(),

                ToggleColumn::make('is_featured')
                    ->label('Featured'),
                ToggleColumn::make('is_premium')
                    ->label('Premium'),
                ToggleColumn::make('is_active')
                    ->label('Live'),
                TextColumn::make('experience')
                    ->searchable(),
                TextColumn::make('job_type')
                    ->searchable(),

                ImageColumn::make('jobSourceImage.image_path')
                    ->label('Ad Image')
                    ->disk('public')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('deactivate_expired')
                    ->label('Deactivate All Expired')
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate All Expired Jobs?')
                    ->modalDescription('This will set "Live" to false for all jobs whose deadline has passed.')
                    ->action(function () {
                        JobListing::where('is_active', true)
                            ->where('deadline', '<', now()->toDateString())
                            ->update(['is_active' => false]);

                        Notification::make()
                            ->title('Expired jobs deactivated successfully.')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),

                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false])),

                    BulkAction::make('extend_deadline')
                        ->label('Extend Deadline (+7 Days)')
                        ->icon('heroicon-o-calendar-days')
                        ->color('warning')
                        ->action(fn (Collection $records) => $records->each(function ($record) {
                            $record->update(['deadline' => Carbon::parse($record->deadline)->addDays(7)]);
                        })),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
