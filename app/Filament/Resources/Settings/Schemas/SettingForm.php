<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Setting Unique Key')
                    ->helperText('e.g. site_name, header_tags, ad_footer')
                    ->required()
                    ->unique('settings', 'key', ignoreRecord: true)
                    ->disabled(fn ($record) => $record !== null),
                Textarea::make('value')
                    ->label(fn ($record) => match ($record?->key) {
                        'site_name' => 'Website Name (Title)',
                        'header_tags' => 'Header HTML (Meta Tags, Analytics)',
                        'ad_footer' => 'Footer Ad Code (AdSense Slot)',
                        'ad_home_top' => 'Homepage Top Ad Code',
                        'whatsapp_alert_number' => 'WhatsApp Alert Contact Number',
                        default => 'Setting Value / Code'
                    })
                    ->rows(10)
                    ->helperText(fn ($record) => match ($record?->key) {
                        'header_tags' => 'Scripts placed inside <head> tag.',
                        'ad_footer' => 'Scripts placed above footer.',
                        'whatsapp_alert_number' => 'Full number with country code (e.g. 923001234567).',
                        default => 'Paste your text or HTML/JS code here.'
                    })
                    ->columnSpanFull(),
            ]);
    }
}
