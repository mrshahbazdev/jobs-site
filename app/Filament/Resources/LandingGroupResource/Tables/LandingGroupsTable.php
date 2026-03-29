<?php

namespace App\Filament\Resources\LandingGroupResource\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class LandingGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sub_label'),
                TextColumn::make('section_type')
                    ->badge(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                ToggleColumn::make('is_active'),
            ])
            ->reorderable('sort_order') // DRAAG & DROP ENABLED
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
