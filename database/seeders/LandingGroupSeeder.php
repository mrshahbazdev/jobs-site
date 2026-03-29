<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LandingGroup;
use App\Models\LandingLink;

class LandingGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            ['name' => 'Testing Services', 'sub_label' => '', 'icon' => 'verified', 'sort_order' => 1, 'section_type' => 'grid'],
            ['name' => 'Overseas', 'sub_label' => '(Bahar ke Mulk)', 'icon' => 'flight_takeoff', 'sort_order' => 2, 'section_type' => 'grid'],
            ['name' => 'Departments', 'sub_label' => '', 'icon' => 'corporate_fare', 'sort_order' => 3, 'section_type' => 'grid'],
            ['name' => 'Education', 'sub_label' => '', 'icon' => 'school', 'sort_order' => 4, 'section_type' => 'grid'],
            ['name' => 'QuickLink', 'sub_label' => '', 'icon' => 'bolt', 'sort_order' => 5, 'section_type' => 'strip'],
            ['name' => 'Industry', 'sub_label' => '', 'icon' => 'factory', 'sort_order' => 6, 'section_type' => 'industry'],
        ];

        foreach ($groups as $groupData) {
            $group = LandingGroup::updateOrCreate(['name' => $groupData['name']], $groupData);
            
            LandingLink::where('group_name', $group->name)->update(['landing_group_id' => $group->id]);
        }
    }
}
