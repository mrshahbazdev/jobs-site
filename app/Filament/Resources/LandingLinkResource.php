<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LandingLinkResource\Pages\CreateLandingLink;
use App\Filament\Resources\LandingLinkResource\Pages\EditLandingLink;
use App\Filament\Resources\LandingLinkResource\Pages\ListLandingLinks;
use App\Filament\Resources\LandingLinkResource\Schemas\LandingLinkForm;
use App\Filament\Resources\LandingLinkResource\Tables\LandingLinksTable;
use App\Models\LandingLink;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class LandingLinkResource extends Resource
{
    protected static ?string $model = LandingLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;
    
    public static function getNavigationGroup(): ?string
    {
        return 'Site Management';
    }
    
    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return LandingLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LandingLinksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandingLinks::route('/'),
            'create' => CreateLandingLink::route('/create'),
            'edit' => EditLandingLink::route('/{record}/edit'),
        ];
    }
}
