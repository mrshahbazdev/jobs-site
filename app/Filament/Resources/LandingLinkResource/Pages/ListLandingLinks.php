<?php

namespace App\Filament\Resources\LandingLinkResource\Pages;

use App\Filament\Resources\LandingLinkResource;
use Filament\Resources\Pages\ListRecords;

class ListLandingLinks extends ListRecords
{
    protected static string $resource = LandingLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
