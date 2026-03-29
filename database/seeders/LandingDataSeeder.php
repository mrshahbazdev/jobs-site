<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LandingGroup;
use App\Models\LandingLink;
use App\Models\Category;
use Illuminate\Support\Str;

class LandingDataSeeder extends Seeder
{
    /**
     * Run the database seeds to map Categories to Groups.
     */
    public function run(): void
    {
        // Define Mappings
        $mappings = [
            'Testing Services' => ['NTS', 'FPSC', 'PPSC', 'PTS', 'OTS', 'ITS'],
            'Overseas' => ['Overseas Jobs', 'Gulf Jobs', 'Dubai Jobs', 'Saudi Jobs'],
            'Top Departments' => ['Police Jobs', 'Army Jobs', 'PAF Jobs', 'Navy Jobs', 'Atomic Energy'],
            'Educational' => ['Teaching Jobs', 'University Jobs', 'School Jobs', 'Lecturer Jobs'],
            'Industry' => ['Banking', 'Engineering', 'Medical', 'IT & Software'],
        ];

        foreach ($mappings as $groupName => $categoryNames) {
            $group = LandingGroup::where('name', $groupName)->first();
            
            if (!$group) {
                $group = LandingGroup::create([
                    'name' => $groupName,
                    'icon' => $this->getIconForGroup($groupName),
                    'is_active' => true,
                    'sort_order' => 10,
                ]);
            }

            foreach ($categoryNames as $index => $catName) {
                // Ensure category exists
                $category = Category::firstOrCreate(
                    ['slug' => Str::slug($catName)],
                    ['name' => $catName, 'icon_name' => 'work']
                );

                // Create link
                LandingLink::updateOrCreate(
                    ['landing_group_id' => $group->id, 'title' => $catName],
                    [
                        'url' => route('categories.show', $category->slug, false),
                        'is_active' => true,
                        'sort_order' => $index,
                    ]
                );
            }
        }
    }

    private function getIconForGroup($name)
    {
        return match($name) {
            'Testing Services' => 'verified',
            'Overseas' => 'flight_takeoff',
            'Top Departments' => 'corporate_fare',
            'Educational' => 'school',
            'Industry' => 'factory',
            default => 'work'
        };
    }
}
