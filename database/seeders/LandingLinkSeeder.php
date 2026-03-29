<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LandingLink;

class LandingLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $links = [
            // Testing Services
            ['label' => 'NTS Jobs', 'group_name' => 'Testing Services', 'route_name' => 'jobs.testing_service', 'route_param' => 'NTS', 'sort_order' => 1],
            ['label' => 'PPSC Jobs', 'group_name' => 'Testing Services', 'route_name' => 'jobs.testing_service', 'route_param' => 'PPSC', 'sort_order' => 2],
            ['label' => 'FPSC Jobs', 'group_name' => 'Testing Services', 'route_name' => 'jobs.testing_service', 'route_param' => 'FPSC', 'sort_order' => 3],
            ['label' => 'SPSC Jobs', 'group_name' => 'Testing Services', 'route_name' => 'jobs.testing_service', 'route_param' => 'SPSC', 'sort_order' => 4],
            ['label' => 'BPSC Jobs', 'group_name' => 'Testing Services', 'route_name' => 'jobs.testing_service', 'route_param' => 'BPSC', 'sort_order' => 5],
            ['label' => 'KPPSC Jobs', 'group_name' => 'Testing Services', 'route_name' => 'jobs.testing_service', 'route_param' => 'KPPSC', 'sort_order' => 6],
            ['label' => 'AJKPSC Jobs', 'group_name' => 'Testing Services', 'route_name' => 'jobs.testing_service', 'route_param' => 'AJKPSC', 'sort_order' => 7],
            ['label' => 'OTS Jobs', 'group_name' => 'Testing Services', 'route_name' => 'jobs.testing_service', 'route_param' => 'OTS', 'sort_order' => 8],
            ['label' => 'PTS Jobs', 'group_name' => 'Testing Services', 'route_name' => 'jobs.testing_service', 'route_param' => 'PTS', 'sort_order' => 9],
            ['label' => 'UTS Jobs', 'group_name' => 'Testing Services', 'route_name' => 'jobs.testing_service', 'route_param' => 'UTS', 'sort_order' => 10],

            // Overseas
            ['label' => 'Saudi Arabia', 'group_name' => 'Overseas', 'route_name' => 'jobs.country', 'route_param' => 'Saudi Arabia', 'sort_order' => 11],
            ['label' => 'UAE (Dubai)', 'group_name' => 'Overseas', 'route_name' => 'jobs.country', 'route_param' => 'UAE', 'sort_order' => 12],
            ['label' => 'Qatar', 'group_name' => 'Overseas', 'route_name' => 'jobs.country', 'route_param' => 'Qatar', 'sort_order' => 13],
            ['label' => 'Oman', 'group_name' => 'Overseas', 'route_name' => 'jobs.country', 'route_param' => 'Oman', 'sort_order' => 14],
            ['label' => 'Kuwait', 'group_name' => 'Overseas', 'route_name' => 'jobs.country', 'route_param' => 'Kuwait', 'sort_order' => 15],
            ['label' => 'USA', 'group_name' => 'Overseas', 'route_name' => 'jobs.country', 'route_param' => 'USA', 'sort_order' => 16],
            ['label' => 'UK', 'group_name' => 'Overseas', 'route_name' => 'jobs.country', 'route_param' => 'UK', 'sort_order' => 17],
            ['label' => 'Canada', 'group_name' => 'Overseas', 'route_name' => 'jobs.country', 'route_param' => 'Canada', 'sort_order' => 18],

            // Departments
            ['label' => 'Police Jobs', 'group_name' => 'Departments', 'route_name' => 'jobs.department', 'route_param' => 'Police', 'sort_order' => 19],
            ['label' => 'Pak Army', 'group_name' => 'Departments', 'route_name' => 'jobs.department', 'route_param' => 'Army', 'sort_order' => 20],
            ['label' => 'Navy Jobs', 'group_name' => 'Departments', 'route_name' => 'jobs.department', 'route_param' => 'Navy', 'sort_order' => 21],
            ['label' => 'FIA Jobs', 'group_name' => 'Departments', 'route_name' => 'jobs.department', 'route_param' => 'FIA', 'sort_order' => 22],
            ['label' => 'ASF Jobs', 'group_name' => 'Departments', 'route_name' => 'jobs.department', 'route_param' => 'ASF', 'sort_order' => 23],
            ['label' => 'ANF Jobs', 'group_name' => 'Departments', 'route_name' => 'jobs.department', 'route_param' => 'ANF', 'sort_order' => 24],
            ['label' => 'Wapda Jobs', 'group_name' => 'Departments', 'route_name' => 'jobs.department', 'route_param' => 'Wapda', 'sort_order' => 25],
            ['label' => 'Railway Jobs', 'group_name' => 'Departments', 'route_name' => 'jobs.department', 'route_param' => 'Railway', 'sort_order' => 26],
            ['label' => 'Rescue 1122', 'group_name' => 'Departments', 'route_name' => 'jobs.department', 'route_param' => 'Rescue 1122', 'sort_order' => 27],
            ['label' => 'Health Jobs', 'group_name' => 'Departments', 'route_name' => 'jobs.department', 'route_param' => 'Health', 'sort_order' => 28],

            // Education
            ['label' => 'Matric Jobs', 'group_name' => 'Education', 'route_name' => 'jobs.education', 'route_param' => 'Matric', 'sort_order' => 29],
            ['label' => 'Inter Jobs', 'group_name' => 'Education', 'route_name' => 'jobs.education', 'route_param' => 'Inter', 'sort_order' => 30],
            ['label' => 'Bachelor Jobs', 'group_name' => 'Education', 'route_name' => 'jobs.education', 'route_param' => 'Bachelor', 'sort_order' => 31],
            ['label' => 'Master Jobs', 'group_name' => 'Education', 'route_name' => 'jobs.education', 'route_param' => 'Master', 'sort_order' => 32],
            ['label' => 'MBBS Jobs', 'group_name' => 'Education', 'route_name' => 'jobs.education', 'route_param' => 'MBBS', 'sort_order' => 33],
            ['label' => 'Engineering', 'group_name' => 'Education', 'route_name' => 'jobs.education', 'route_param' => 'Engineering', 'sort_order' => 34],
            ['label' => 'MBA Jobs', 'group_name' => 'Education', 'route_name' => 'jobs.education', 'route_param' => 'MBA', 'sort_order' => 35],
            ['label' => 'CA Jobs', 'group_name' => 'Education', 'route_name' => 'jobs.education', 'route_param' => 'CA', 'sort_order' => 36],
            ['label' => 'Nursing Jobs', 'group_name' => 'Education', 'route_name' => 'jobs.education', 'route_param' => 'Nursing', 'sort_order' => 37],
            ['label' => 'DAE Jobs', 'group_name' => 'Education', 'route_name' => 'jobs.education', 'route_param' => 'DAE', 'sort_order' => 38],

            // Industry
            ['label' => 'Banking', 'group_name' => 'Industry', 'route_name' => 'jobs.industrial', 'route_param' => 'Banking & Finance', 'sort_order' => 39],
            ['label' => 'Telecom', 'group_name' => 'Industry', 'route_name' => 'jobs.industrial', 'route_param' => 'Telecommunications', 'sort_order' => 40],
            ['label' => 'Textile', 'group_name' => 'Industry', 'route_name' => 'jobs.industrial', 'route_param' => 'Textile', 'sort_order' => 41],
            ['label' => 'Pharma', 'group_name' => 'Industry', 'route_name' => 'jobs.industrial', 'route_param' => 'Pharmaceutical', 'sort_order' => 42],
            ['label' => 'Real Estate', 'group_name' => 'Industry', 'route_name' => 'jobs.industrial', 'route_param' => 'Real Estate', 'sort_order' => 43],
            ['label' => 'Automotive', 'group_name' => 'Industry', 'route_name' => 'jobs.industrial', 'route_param' => 'Automotive', 'sort_order' => 44],

            // QuickLinks
            ['label' => 'Today\'s New Jobs', 'group_name' => 'QuickLink', 'route_name' => 'jobs.today', 'route_param' => null, 'sort_order' => 45, 'icon' => 'notification_important'],
            ['label' => 'With Hostel', 'group_name' => 'QuickLink', 'route_name' => 'jobs.accommodation', 'route_param' => null, 'sort_order' => 46, 'icon' => 'house'],
            ['label' => 'Pick & Drop', 'group_name' => 'QuickLink', 'route_name' => 'jobs.transport', 'route_param' => null, 'sort_order' => 47, 'icon' => 'directions_bus'],
            ['label' => 'Govt Jobs', 'group_name' => 'QuickLink', 'route_name' => 'jobs.sector', 'route_param' => 'Government', 'sort_order' => 48, 'icon' => 'account_balance'],
        ];

        foreach ($links as $link) {
            LandingLink::updateOrCreate(
                ['label' => $link['label'], 'group_name' => $link['group_name']],
                $link
            );
        }
    }
}
