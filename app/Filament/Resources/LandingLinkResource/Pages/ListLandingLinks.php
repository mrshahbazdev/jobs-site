<?php

namespace App\Filament\Resources\LandingLinkResource\Pages;

use App\Filament\Resources\LandingLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLandingLinks extends ListRecords
{
    protected static string $resource = LandingLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
