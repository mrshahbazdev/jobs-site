<?php

namespace App\Filament\Resources\LandingGroupResource\Pages;

use App\Filament\Resources\LandingGroupResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;

class EditLandingGroup extends EditRecord
{
    protected static string $resource = LandingGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
