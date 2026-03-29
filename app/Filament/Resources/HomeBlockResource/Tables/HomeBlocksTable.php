<?php

namespace App\Filament\Resources\HomeBlockResource\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class HomeBlocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('page_slug')
                    ->label('Page')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'home' => 'primary',
                        'all-lists' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->searchable()
                    ->badge(),
                TextColumn::make('title')
                    ->searchable(),
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
