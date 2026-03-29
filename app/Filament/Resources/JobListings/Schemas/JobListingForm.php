<?php

namespace App\Filament\Resources\JobListings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Actions\Action;
use Filament\Schemas\Schema;
use App\Services\GeminiService;
use App\Models\Category;
use App\Models\City;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class JobListingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->lazy()
                    ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state))),
                
                TextInput::make('slug')
                    ->required(),

                Select::make('category_id')
                    ->label('Category')
                    ->options(Category::all()->pluck('name', 'id'))
                    ->required(),

                Select::make('city_id')
                    ->label('City')
                    ->options(City::all()->pluck('name', 'id'))
                    ->required(),

                RichEditor::make('description')
                    ->columnSpanFull()
                    ->required(),

                Toggle::make('is_featured'),
                Toggle::make('is_premium')
                    ->label('Premium Listing')
                    ->helperText('Highlight this job on the frontend.'),
                Toggle::make('is_active')
                    ->label('Published'),

                TextInput::make('whatsapp_number')
                    ->label('WhatsApp Contact')
                    ->placeholder('e.g. 923001234567')
                    ->tel(),

                TextInput::make('company_name')
                    ->label('Company Name')
                    ->placeholder('e.g. Google, Private Limited'),

                FileUpload::make('company_logo')
                    ->label('Company Logo')
                    ->image()
                    ->directory('company-logos')
                    ->disk('public'),

                TextInput::make('salary_min')
                    ->label('Minimum Salary')
                    ->numeric()
                    ->prefix('PKR'),
                
                TextInput::make('salary_max')
                    ->label('Maximum Salary')
                    ->numeric()
                    ->prefix('PKR'),

                Select::make('education')
                    ->options([
                        'Matric' => 'Matric',
                        'Intermediate' => 'Intermediate',
                        'Bachelor' => 'Bachelor',
                        'Master' => 'Master',
                        'PhD' => 'PhD',
                        'M.Phil' => 'M.Phil',
                        'DAE' => 'DAE',
                        'Nursing' => 'Nursing',
                        'Teaching' => 'Teaching',
                        'Literate' => 'Literate',
                    ])
                    ->searchable()
                    ->placeholder('Select Education Level'),

                Select::make('newspaper')
                    ->options([
                        'Jang' => 'Jang',
                        'Express' => 'Express',
                        'Dawn' => 'Dawn',
                        'The News' => 'The News',
                        'Nawaiwaqt' => 'Nawaiwaqt',
                        'Nation' => 'Nation',
                        'Kawish' => 'Kawish',
                        'Mashriq' => 'Mashriq',
                        'Aaj' => 'Aaj',
                        'Dunaya' => 'Dunaya',
                    ])
                    ->searchable()
                    ->placeholder('Select Newspaper'),

                Select::make('province')
                    ->options([
                        'Punjab' => 'Punjab',
                        'Sindh' => 'Sindh',
                        'KPK' => 'Khyber Pakhtunkhwa',
                        'Balochistan' => 'Balochistan',
                        'AJK' => 'Azad Kashmir',
                        'GB' => 'Gilgit Baltistan',
                        'ICT' => 'Islamabad',
                    ])
                    ->searchable()
                    ->placeholder('Select Province'),

                Select::make('gender')
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                        'Both' => 'Both / Any',
                    ])
                    ->placeholder('Select Gender'),

                Select::make('bps_scale')
                    ->label('BPS (Scale)')
                    ->options(collect(range(1, 22))->mapWithKeys(fn($i) => ["BPS-".str_pad($i, 2, '0', STR_PAD_LEFT) => "BPS-".str_pad($i, 2, '0', STR_PAD_LEFT)]))
                    ->searchable()
                    ->placeholder('Select BPS Scale'),

                Select::make('testing_service')
                    ->options([
                        'NTS' => 'NTS (National Testing Service)',
                        'PPSC' => 'PPSC (Punjab Public Service Commission)',
                        'FPSC' => 'FPSC (Federal Public Service Commission)',
                        'SPSC' => 'SPSC (Sindh Public Service Commission)',
                        'BPSC' => 'BPSC (Balochistan Public Service Commission)',
                        'KPPSC' => 'KPPSC (KPK Public Service Commission)',
                        'AJKPSC' => 'AJKPSC (AJK Public Service Commission)',
                        'OTS' => 'OTS (Open Testing Service)',
                        'PTS' => 'PTS (Pakistan Testing Service)',
                        'UTS' => 'UTS (Universal Testing Service)',
                    ])
                    ->searchable()
                    ->placeholder('Select Testing Service'),

                Select::make('sector')
                    ->options([
                        'Government' => 'Government (Sarkari)',
                        'Private' => 'Private Sector',
                        'Semi-Government' => 'Semi-Government',
                        'NGO' => 'NGO / International',
                    ])
                    ->placeholder('Select Sector'),

                Toggle::make('is_overseas')
                    ->label('Is Overseas Job?')
                    ->reactive(),

                Select::make('country')
                    ->label('Overseas Country')
                    ->options([
                        'Saudi Arabia' => 'Saudi Arabia',
                        'UAE' => 'United Arab Emirates (Dubai)',
                        'Qatar' => 'Qatar',
                        'Oman' => 'Oman',
                        'Kuwait' => 'Kuwait',
                        'USA' => 'USA',
                        'UK' => 'United Kingdom',
                        'Canada' => 'Canada',
                        'Australia' => 'Australia',
                    ])
                    ->visible(fn ($get) => $get('is_overseas'))
                    ->searchable()
                    ->placeholder('Select Country'),

                TextInput::make('job_role')
                    ->label('Specific Job Role / Title')
                    ->placeholder('e.g. Computer Operator, Security Guard, Driver')
                    ->datalist([
                        'Computer Operator', 'Data Entry Operator', 'Driver', 'Security Guard', 'Clerk', 'Junior Clerk', 'Assistant', 'Accountant', 'Receptionist', 'HR Manager', 'Sales Marketing', 'Cook', 'Sweeper', 'Naib Qasid', 'Patwari', 'Teacher', 'Professor', 'Lecturer', 'Doctor', 'Nurse', 'Engineer'
                    ]),

                Select::make('sub_sector')
                    ->label('Industrial Sub-Sector')
                    ->options([
                        'Banking & Finance' => 'Banking & Finance',
                        'Telecommunications' => 'Telecommunications',
                        'Pharmaceutical' => 'Pharmaceutical',
                        'Textile' => 'Textile',
                        'FMCG' => 'FMCG (Food/Beverages)',
                        'Real Estate' => 'Real Estate',
                        'Automotive' => 'Automotive',
                        'Poultry' => 'Poultry',
                        'Agriculture' => 'Agriculture',
                        'NGO' => 'NGO',
                        'IT & Software' => 'IT & Software',
                        'Marketing & Sales' => 'Marketing & Sales',
                        'Construction' => 'Construction',
                        'Oil & Gas' => 'Oil & Gas',
                    ])
                    ->searchable()
                    ->placeholder('Select Sub-sector'),

                Select::make('contract_type')
                    ->label('Job Contract / Type')
                    ->options([
                        'Permanent' => 'Permanent',
                        'Contract Basis' => 'Contract Basis',
                        'Ad-hoc' => 'Ad-hoc Basis',
                        'Daily Wages' => 'Daily Wages',
                        'Contingent Paid' => 'Contingent Paid',
                    ])
                    ->placeholder('Select Type'),

                TextInput::make('skills')
                    ->label('Technical Skills')
                    ->placeholder('e.g. MS Office, Typing, Graphics Design (Comma separated)')
                    ->datalist([
                        'MS Office', 'Typing', 'Graphics Design', 'Web Development', 'SEO', 'Digital Marketing', 'ACCA/CA', 'HR Management', 'Data Entry'
                    ]),

                Toggle::make('is_remote')
                    ->label('Work From Home (Remote)'),
                
                Toggle::make('has_walkin_interview')
                    ->label('Walk-in Interview Available'),

                Toggle::make('is_whatsapp_apply')
                    ->label('WhatsApp Applying Method'),

                Toggle::make('is_retired_army')
                    ->label('Jobs for Retired Army Officers'),

                Toggle::make('is_student_friendly')
                    ->label('Jobs for Students'),

                Toggle::make('has_accommodation')
                    ->label('Free Accommodation (Hostel)'),

                Toggle::make('has_transport')
                    ->label('Free Transport (Pick & Drop)'),

                Toggle::make('has_medical_insurance')
                    ->label('Medical Insurance / Facility'),

                Select::make('registration_council')
                    ->label('Registration Body / Council')
                    ->options([
                        'PEC' => 'PEC (Pakistan Engineering Council)',
                        'PMC' => 'PMC (Pakistan Medical Commission)',
                        'Nursing' => 'Nursing Council',
                        'Law' => 'Bar Council',
                        'Pharmacy' => 'Pharmacy Council',
                    ])
                    ->placeholder('Select Council (Optional)'),

                TextInput::make('qualification_degree')
                    ->label('Minimum Qualification / Degree')
                    ->placeholder('e.g. MBBS, MBA, BS Software Engineering')
                    ->datalist([
                        'MBBS', 'MBA', 'BSCS', 'BBA', 'LLB', 'CA', 'ACCA', 'BS Electrical', 'BS Civil', 'Nursing', 'Pharmacy'
                    ]),

                Toggle::make('is_special_quota')
                    ->label('Special Person (Disabled) Quota'),

                Toggle::make('is_minority_quota')
                    ->label('Minority Quota'),

                TextInput::make('experience')
                    ->placeholder('e.g. 1-2 Years, Fresh'),
                TextInput::make('job_type')
                    ->placeholder('e.g. Full-time, Contract'),

                TextInput::make('meta_description')
                    ->columnSpanFull()
                    ->maxLength(160)
                    ->suffixAction(
                        Action::make('ai_extract')
                            ->icon('heroicon-m-sparkles')
                            ->tooltip('Extract SEO & Details with AI')
                            ->action(function ($set, $state, $get) {
                                $html = $get('description');
                                if (!$html) return;
                                
                                $data = GeminiService::extractMetadata($html);
                                if (!empty($data)) {
                                    $set('meta_description', $data['meta_description'] ?? '');
                                    $set('meta_keywords', $data['meta_keywords'] ?? '');
                                    $set('experience', $data['experience'] ?? '');
                                    $set('job_type', $data['job_type'] ?? '');
                                    $set('salary_min', $data['salary_min'] ?? null);
                                    $set('salary_max', $data['salary_max'] ?? null);
                                }
                            })
                    ),
                TextInput::make('meta_keywords')
                    ->columnSpanFull()
                    ->placeholder('comma, separated, keywords'),

                FileUpload::make('image_path')
                    ->label('Thumbnail (If any)')
                    ->disk('public')
                    ->image()
                    ->directory('job-listings')
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        null,
                        '16:9',
                        '4:3',
                        '1:1',
                    ]),

                Placeholder::make('source_image')
                    ->label('Source Image Advertisement')
                    ->content(function ($record) {
                        if (!$record || !$record->jobSourceImage || !$record->jobSourceImage->local_image_path) {
                            return 'No image attached';
                        }
                        $path = storage_path('app/public/' . $record->jobSourceImage->local_image_path);
                        $url = url('storage/' . $record->jobSourceImage->local_image_path);
                        return new HtmlString('<img src="' . $url . '" style="max-height: 400px; border: 1px solid #ccc;">');
                    }),
            ]);
    }
}
