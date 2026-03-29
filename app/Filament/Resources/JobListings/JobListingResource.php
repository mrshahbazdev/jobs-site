<?php

namespace App\Filament\Resources\JobListings;

use App\Filament\Resources\JobListings\Pages\CreateJobListing;
use App\Filament\Resources\JobListings\Pages\EditJobListing;
use App\Filament\Resources\JobListings\Pages\ListJobListings;
use App\Filament\Resources\JobListings\Schemas\JobListingForm;
use App\Filament\Resources\JobListings\Tables\JobListingsTable;
use App\Models\JobListing;
use BackedEnum;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class JobListingResource extends Resource
{
    protected static ?string $model = JobListing::class;
    
    public static function getNavigationLabel(): string
    {
        return 'Manual Jobs';
    }

    public static function getModelLabel(): string
    {
        return 'Manual Job';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Jobs Management';
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'manual-jobs';
    }

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return JobListingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobListingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobListings::route('/'),
            'create' => CreateJobListing::route('/create'),
            'edit' => EditJobListing::route('/{record}/edit'),
        ];
    }
}
