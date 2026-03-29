<?php

namespace App\Filament\Resources\LandingLinkResource\Pages;

use App\Filament\Resources\LandingLinkResource;
use Filament\Resources\Pages\EditRecord;

class EditLandingLink extends EditRecord
{
    protected static string $resource = LandingLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
