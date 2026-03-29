<?php

namespace App\Filament\Resources\LandingLinkResource\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LandingLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->required()
                    ->placeholder('e.g. NTS Jobs, Police Jobs, Qatar Jobs'),
                
                Select::make('landing_group_id')
                    ->label('Landing Section')
                    ->required()
                    ->relationship('group', 'name')
                    ->searchable(),

                Select::make('route_name')
                    ->required()
                    ->options([
                        'jobs.testing_service' => 'Testing Service List',
                        'jobs.country' => 'Country Job List',
                        'jobs.department' => 'Department List',
                        'jobs.education' => 'Education Level List',
                        'jobs.industrial' => 'Industry List',
                        'jobs.today' => 'Today\'s Jobs',
                        'jobs.accommodation' => 'Hostel Jobs',
                        'jobs.transport' => 'Transport Jobs',
                        'jobs.sector' => 'Sector (Govt/Private)',
                        'jobs.bps' => 'BPS Scale List',
                        'jobs.province' => 'Province List',
                    ])
                    ->searchable(),

                TextInput::make('route_param')
                    ->helperText('The actual value (e.g. NTS, Punjab, Saudi Arabia)'),

                TextInput::make('icon')
                    ->placeholder('e.g. verified, school, chat')
                    ->helperText('Material Symbols icon name.'),

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
