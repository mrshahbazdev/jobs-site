<?php

namespace App\Filament\Resources\HomeBlockResource\Pages;

use App\Filament\Resources\HomeBlockResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListHomeBlocks extends ListRecords
{
    protected static string $resource = HomeBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
