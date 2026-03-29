<?php

namespace App\Filament\Resources\LandingLinkResource\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class LandingLinksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group_name')
                    ->badge()
                    ->sortable(),
                TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('route_name')
                    ->description(fn ($record) => $record->route_param)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                ToggleColumn::make('is_active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group_name')
                    ->options([
                        'Testing Services' => 'Testing Services',
                        'Overseas' => 'Overseas',
                        'Departments' => 'Departments',
                        'Education' => 'Education',
                        'Industry' => 'Industry',
                        'QuickLink' => 'QuickLink',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc');
    }
}
