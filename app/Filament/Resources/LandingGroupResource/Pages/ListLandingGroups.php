<?php

namespace App\Filament\Resources\LandingGroupResource\Pages;

use App\Filament\Resources\LandingGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
