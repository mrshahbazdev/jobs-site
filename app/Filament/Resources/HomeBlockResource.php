<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomeBlockResource\Pages\CreateHomeBlock;
use App\Filament\Resources\HomeBlockResource\Pages\EditHomeBlock;
use App\Filament\Resources\HomeBlockResource\Pages\ListHomeBlocks;
use App\Filament\Resources\HomeBlockResource\Schemas\HomeBlockForm;
use App\Filament\Resources\HomeBlockResource\Tables\HomeBlocksTable;
use App\Models\HomeBlock;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class HomeBlockResource extends Resource
{
    protected static ?string $model = HomeBlock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;
    
    public static function getNavigationGroup(): ?string
    {
        return 'Site Management';
    }

    public static function getNavigationLabel(): string
    {
        return 'Site Builder';
    }
    
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return HomeBlockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeBlocksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeBlocks::route('/'),
            'create' => CreateHomeBlock::route('/create'),
            'edit' => EditHomeBlock::route('/{record}/edit'),
        ];
    }
}
