<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HomeBlock;

class HomeBlockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // --- HEADER BLOCKS ---
        $headerBlocks = [
            [
                'page_slug' => 'header',
                'type' => 'header_logo',
                'title' => 'JobsPic.com',
                'icon' => 'work',
                'sort_order' => 1,
            ],
            [
                'page_slug' => 'header',
                'type' => 'nav_link',
                'title' => 'Categories',
                'url' => '/categories',
                'sort_order' => 2,
            ],
            [
                'page_slug' => 'header',
                'type' => 'nav_link',
                'title' => 'Career Advice',
                'url' => '/blog',
                'sort_order' => 3,
            ],
            [
                'page_slug' => 'header',
                'type' => 'nav_link',
                'title' => 'Upload CV',
                'url' => 'https://wa.me/923000000000?text=Hi, I want to submit my resume.',
                'icon' => 'upload_file',
                'sort_order' => 4,
            ],
            [
                'page_slug' => 'header',
                'type' => 'nav_link',
                'title' => 'Join WhatsApp',
                'url' => 'https://chat.whatsapp.com/your-group-link',
                'icon' => 'chat',
                'sort_order' => 5,
            ],
        ];

        // --- HOMEPAGE BLOCKS ---
        $homeBlocks = [
            [
                'page_slug' => 'home',
                'type' => 'hero_cards',
                'title' => 'Top Feature Cards',
                'sort_order' => 1,
                'cards' => [
                    ['label' => 'Direct Entry', 'title' => 'Walk-in Interviews', 'sub_title' => 'No test required, direct interviews.', 'icon' => 'hail', 'url' => 'jobs.walkin'],
                    ['label' => 'Easiest Way', 'title' => 'WhatsApp Apply', 'sub_title' => 'Apply directly via WhatsApp number.', 'icon' => 'chat', 'url' => 'jobs.whatsapp'],
                    ['label' => 'Work from Home', 'title' => 'Remote Jobs', 'sub_title' => 'International & local remote work.', 'icon' => 'distance', 'url' => 'jobs.remote']
                ],
            ],
            [
                'page_slug' => 'home',
                'type' => 'heading',
                'title' => 'Category Heading',
                'sort_order' => 2,
                'heading_text' => 'Explore 100+ Job Categories',
                'sub_text' => 'Find exactly what you\'re looking for by browsing our specialized lists.',
            ],
            [
                'page_slug' => 'home',
                'type' => 'category_grids',
                'title' => '100+ Categories Grid',
                'sort_order' => 3,
            ],
            [
                'page_slug' => 'home',
                'type' => 'featured_jobs',
                'title' => 'Featured Row',
                'sort_order' => 4,
                'job_count' => 4,
            ],
            [
                'page_slug' => 'home',
                'type' => 'whatsapp_cta',
                'title' => 'Large WhatsApp Alert',
                'sort_order' => 5,
                'variant' => 'large',
            ],
            [
                'page_slug' => 'home',
                'type' => 'latest_jobs_list',
                'title' => 'Main Jobs List & Sidebar',
                'sort_order' => 6,
                'show_sidebar' => true,
            ],
            [
                'page_slug' => 'home',
                'type' => 'newsletter',
                'title' => 'Footer Subscription',
                'sort_order' => 7,
            ],
        ];

        // --- ALL LISTS PAGE BLOCKS ---
        $allListsBlocks = [
            [
                'page_slug' => 'all-lists',
                'type' => 'heading',
                'title' => 'Main Header',
                'sort_order' => 1,
                'heading_text' => 'Browse All Job Lists',
                'sub_text' => 'Find jobs by any category, testing service, education, or location.',
            ],
            [
                'page_slug' => 'all-lists',
                'type' => 'multi_list',
                'title' => 'Provinces List',
                'sort_order' => 3,
                'heading_text' => 'By Province',
                'icon' => 'map',
                'list_source' => 'provinces',
                'display_type' => 'list',
            ],
            [
                'page_slug' => 'all-lists',
                'type' => 'multi_list',
                'title' => 'Industry List',
                'sort_order' => 4,
                'heading_text' => 'Industry Hub',
                'icon' => 'factory',
                'list_source' => 'industries',
                'display_type' => 'list',
            ],
        ];

        // --- FOOTER BLOCKS ---
        $footerBlocks = [
            [
                'page_slug' => 'footer',
                'type' => 'footer_column',
                'title' => 'For Candidates',
                'sort_order' => 1,
                'cards' => [
                    ['title' => 'Today\'s New Jobs', 'url' => 'jobs.today', 'icon' => 'bolt'],
                    ['title' => 'Govt Jobs', 'url' => 'jobs.sector/Government', 'icon' => 'verified'],
                    ['title' => 'All Categories', 'url' => 'jobs.all_lists', 'icon' => 'apps'],
                ],
            ],
            [
                'page_slug' => 'footer',
                'type' => 'footer_column',
                'title' => 'Legal',
                'sort_order' => 2,
                'cards' => [
                    ['title' => 'About Us', 'url' => '/about'],
                    ['title' => 'Terms & Conditions', 'url' => '/terms'],
                    ['title' => 'Privacy Policy', 'url' => '/privacy-policy'],
                ],
            ],
            [
                'page_slug' => 'footer',
                'type' => 'footer_copyright',
                'title' => 'Copyright Block',
                'heading_text' => 'JobsPic.com. All rights reserved.',
                'sort_order' => 10,
            ],
        ];

        $allData = array_merge($headerBlocks, $homeBlocks, $allListsBlocks, $footerBlocks);

        foreach ($allData as $block) {
            HomeBlock::updateOrCreate(
                ['page_slug' => $block['page_slug'], 'title' => $block['title']], 
                $block
            );
        }
    }
}
