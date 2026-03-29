<?php

namespace App\Filament\Resources\JobSourceImages\Pages;

use App\Filament\Resources\JobSourceImages\JobSourceImageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJobSourceImages extends ListRecords
{
    protected static string $resource = JobSourceImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
