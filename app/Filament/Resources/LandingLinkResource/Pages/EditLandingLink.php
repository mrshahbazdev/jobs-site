<?php

namespace App\Filament\Resources\LandingLinkResource\Pages;

use App\Filament\Resources\LandingLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLandingLink extends EditRecord
{
    protected static string $resource = LandingLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
