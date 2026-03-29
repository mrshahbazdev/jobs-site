<?php

namespace App\Filament\Resources\HomeBlockResource\Pages;

use App\Filament\Resources\HomeBlockResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;

class EditHomeBlock extends EditRecord
{
    protected static string $resource = HomeBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
