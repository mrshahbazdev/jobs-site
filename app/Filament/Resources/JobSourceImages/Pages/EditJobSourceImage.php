<?php

namespace App\Filament\Resources\JobSourceImages\Pages;

use App\Filament\Resources\JobSourceImages\JobSourceImageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJobSourceImage extends EditRecord
{
    protected static string $resource = JobSourceImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
