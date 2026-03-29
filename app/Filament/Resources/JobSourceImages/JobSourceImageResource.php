<?php

namespace App\Filament\Resources\JobSourceImages;

use App\Filament\Resources\JobSourceImages\Pages\CreateJobSourceImage;
use App\Filament\Resources\JobSourceImages\Pages\EditJobSourceImage;
use App\Filament\Resources\JobSourceImages\Pages\ListJobSourceImages;
use App\Filament\Resources\JobSourceImages\Schemas\JobSourceImageForm;
use App\Filament\Resources\JobSourceImages\Tables\JobSourceImagesTable;
use App\Models\JobSourceImage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class JobSourceImageResource extends Resource
{
    protected static ?string $model = JobSourceImage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Jobs Management';

    public static function form(Schema $schema): Schema
    {
        return JobSourceImageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobSourceImagesTable::configure($table);
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
            'index' => ListJobSourceImages::route('/'),
            'create' => CreateJobSourceImage::route('/create'),
            'edit' => EditJobSourceImage::route('/{record}/edit'),
        ];
    }
}
