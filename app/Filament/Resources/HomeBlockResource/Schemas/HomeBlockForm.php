<?php

namespace App\Filament\Resources\HomeBlockResource\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HomeBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('page_slug')
                    ->label('Page')
                    ->options([
                        'home' => 'Homepage',
                        'all-lists' => 'All Job Lists Page',
                        'header' => 'Site Header (Global)',
                        'footer' => 'Site Footer (Global)',
                    ])
                    ->required()
                    ->default('home'),

                Select::make('type')
                    ->required()
                    ->options([
                        'hero_cards' => 'Hero Feature Cards (Walk-in, WhatsApp, Remote)',
                        'category_grids' => 'Specialized Category Grids (100+ categories)',
                        'featured_jobs' => 'Featured Job Cards',
                        'latest_jobs_list' => 'Latest Jobs List with Filters',
                        'whatsapp_cta' => 'WhatsApp Joining Alert (Large)',
                        'heading' => 'Section Heading / Banner',
                        'multi_list' => 'Category List Group (Provinces, Industries, etc.)',
                        'header_logo' => 'Header: Site Logo & Name',
                        'nav_link' => 'Header/Footer: Navigation Link',
                        'footer_column' => 'Footer: Column of Links',
                        'footer_copyright' => 'Footer: Copyright & Bottom Bar',
                    ])
                    ->reactive(),

                TextInput::make('title')
                    ->placeholder('Internal name for this block'),

                TextInput::make('heading_text')
                    ->label(fn ($get) => $get('type') === 'footer_copyright' ? 'Copyright Text' : 'Heading Text')
                    ->visible(fn ($get) => in_array($get('type'), ['heading', 'newsletter', 'whatsapp_cta', 'multi_list', 'footer_copyright'])),
                TextInput::make('sub_text')
                    ->visible(fn ($get) => in_array($get('type'), ['heading', 'newsletter', 'whatsapp_cta', 'multi_list'])),

                TextInput::make('url')
                    ->label('Link URL / Route Name')
                    ->placeholder('e.g. jobs.today or /about')
                    ->visible(fn ($get) => in_array($get('type'), ['nav_link', 'header_logo'])),

                Select::make('list_source')
                    ->options([
                        'provinces' => 'Provinces',
                        'education' => 'Education Levels',
                        'testing' => 'Testing Services',
                        'roles' => 'Job Roles',
                        'overseas' => 'Overseas Countries',
                        'councils' => 'Professional Councils',
                        'sectors' => 'Sectors (Govt/Private)',
                        'industries' => 'Industrial Hubs',
                        'contracts' => 'Contract Types',
                        'skills' => 'Technical Skills',
                        'categories' => 'Main Categories (Database)',
                        'cities' => 'Top Cities (Database)',
                        'archives' => 'Monthly Archives',
                    ])
                    ->visible(fn ($get) => $get('type') === 'multi_list'),

                Select::make('display_type')
                    ->options([
                        'list' => 'List with Arrows',
                        'grid' => 'Pill Grid',
                    ])
                    ->default('list')
                    ->visible(fn ($get) => $get('type') === 'multi_list'),
                
                \Filament\Forms\Components\Repeater::make('cards')
                    ->label(fn ($get) => $get('type') === 'footer_column' ? 'Links in this Column' : 'Section Items (Max 4)')
                    ->visible(fn ($get) => in_array($get('type'), ['hero_cards', 'footer_column']))
                    ->schema([
                        TextInput::make('label')
                            ->label('Badge / Small Text')
                            ->placeholder('e.g. New, Hot')
                            ->hidden(fn ($get) => $get('../../type') === 'footer_column'),
                        TextInput::make('title')
                            ->label(fn ($get) => $get('../../type') === 'footer_column' ? 'Link Text' : 'Main Title')
                            ->required(),
                        TextInput::make('sub_title')
                            ->label('Helper Text')
                            ->hidden(fn ($get) => $get('../../type') === 'footer_column'),
                        Select::make('icon')
                            ->searchable()
                            ->options([
                                'verified' => 'Verified / Check',
                                'stars' => 'Star / Featured',
                                'school' => 'Education / Degree',
                                'flight_takeoff' => 'Overseas / Flight',
                                'corporate_fare' => 'Department / Building',
                                'work' => 'Job / Briefcase',
                                'chat' => 'WhatsApp / Chat',
                                'hail' => 'Walk-in / Interview',
                                'distance' => 'Remote / Location',
                                'list_alt' => 'List / Document',
                                'apps' => 'Browse / Categories',
                                'bolt' => 'Quick / Flash',
                                'factory' => 'Industry / Factory',
                                'public' => 'Public / Web',
                                'alternate_email' => 'Email / @',
                            ]),
                        TextInput::make('url')
                            ->label('URL / Route Name')
                            ->placeholder('e.g. jobs.walkin or /page')
                            ->required(),
                    ])
                    ->columns(2)
                    ->grid(fn ($get) => $get('type') === 'footer_column' ? 1 : 2),

                Select::make('job_count')
                    ->label('Number of Jobs to show')
                    ->options([2 => '2', 4 => '4', 6 => '6', 8 => '8', 10 => '10'])
                    ->visible(fn ($get) => in_array($get('type'), ['featured_jobs', 'latest_jobs_list'])),

                Toggle::make('show_sidebar')
                    ->default(true)
                    ->visible(fn ($get) => $get('type') === 'latest_jobs_list'),

                Select::make('variant')
                    ->options(['simple' => 'Simple', 'large' => 'Large (Premium)'])
                    ->visible(fn ($get) => $get('type') === 'whatsapp_cta'),

                Select::make('icon')
                    ->searchable()
                    ->options([
                        'verified' => 'Verified / Check',
                        'stars' => 'Star / Featured',
                        'school' => 'Education / Degree',
                        'flight_takeoff' => 'Overseas / Flight',
                        'corporate_fare' => 'Department / Building',
                        'work' => 'Job / Briefcase',
                        'chat' => 'WhatsApp / Chat',
                        'hail' => 'Walk-in / Interview',
                        'distance' => 'Remote / Location',
                        'list_alt' => 'List / Document',
                        'apps' => 'Browse / Categories',
                        'bolt' => 'Quick / Flash',
                        'factory' => 'Industry / Factory',
                    ])
                    ->helperText('Select a Material Symbols icon for this section.'),

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
