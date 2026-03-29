<?php

namespace App\Filament\Resources\LandingGroupResource\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LandingGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->placeholder('e.g. Testing Services, Special Offers'),
                
                TextInput::make('sub_label')
                    ->placeholder('e.g. (Bahar ke Mulk)'),

                TextInput::make('icon')
                    ->placeholder('e.g. verified, flight_takeoff')
                    ->helperText('Material Symbols icon name.'),

                Select::make('section_type')
                    ->required()
                    ->options([
                        'grid' => 'Standard Grid (Cols)',
                        'strip' => 'Quick Strip (Bottom)',
                        'industry' => 'Industrial Hub (Boxes)',
                    ])
                    ->default('grid'),

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
