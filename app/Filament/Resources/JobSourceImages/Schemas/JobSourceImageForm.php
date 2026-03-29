<?php

namespace App\Filament\Resources\JobSourceImages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class JobSourceImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->required(),
                TextInput::make('source_page_url')->label('Source Page URL')->url()->required(),
                TextInput::make('source_image_url')->label('Deep Image URL')->url(),
                TextInput::make('local_url')
                    ->label('Your Hosting Image Link')
                    ->afterStateHydrated(function (TextInput $component, $record) {
                        if ($record && $record->local_image_path) {
                            $path = storage_path('app/public/' . $record->local_image_path);
                            $version = file_exists($path) ? filemtime($path) : time();
                            $component->state(url('storage/' . $record->local_image_path) . '?t=' . $version);
                        }
                    })
                    ->readonly()
                    ->copyable(),
                FileUpload::make('local_image_path')
                    ->label('Advertisement Image')
                    ->disk('public')
                    ->image()
                    ->directory('job-sources')
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        null,
                        '16:9',
                        '4:3',
                        '1:1',
                    ]),
                Toggle::make('is_processed'),
            ]);
    }
}
