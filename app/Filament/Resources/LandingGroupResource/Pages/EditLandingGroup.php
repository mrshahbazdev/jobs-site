<?php

namespace App\Filament\Resources\LandingGroupResource\Pages;

use App\Filament\Resources\LandingGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

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
