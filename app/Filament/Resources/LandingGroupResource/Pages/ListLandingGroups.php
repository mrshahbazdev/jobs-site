<?php

namespace App\Filament\Resources\LandingGroupResource\Pages;

use App\Filament\Resources\LandingGroupResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListLandingGroups extends ListRecords
{
    protected static string $resource = LandingGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
