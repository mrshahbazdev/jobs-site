<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LandingGroupResource\Pages\CreateLandingGroup;
use App\Filament\Resources\LandingGroupResource\Pages\EditLandingGroup;
use App\Filament\Resources\LandingGroupResource\Pages\ListLandingGroups;
use App\Filament\Resources\LandingGroupResource\Schemas\LandingGroupForm;
use App\Filament\Resources\LandingGroupResource\Tables\LandingGroupsTable;
use App\Models\LandingGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class LandingGroupResource extends Resource
{
    protected static ?string $model = LandingGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
    
    public static function getNavigationGroup(): ?string
    {
        return 'Site Management';
    }

    public static function getNavigationLabel(): string
    {
        return 'Landing Sections';
    }
    
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return LandingGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LandingGroupsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandingGroups::route('/'),
            'create' => CreateLandingGroup::route('/create'),
            'edit' => EditLandingGroup::route('/{record}/edit'),
        ];
    }
}
