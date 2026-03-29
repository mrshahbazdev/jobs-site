<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@jobspic.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        $categories = ['Government Jobs', 'Private Sector', 'FPSC', 'PPSC', 'NTS', 'Banking', 'Engineering', 'Medical'];
        foreach ($categories as $cat) {
            \App\Models\Category::create([
                'name' => $cat,
                'slug' => \Illuminate\Support\Str::slug($cat),
                'icon_name' => 'work',
            ]);
        }

        $cities = ['Islamabad', 'Karachi', 'Lahore', 'Rawalpindi', 'Peshawar', 'Quetta', 'Faisalabad', 'Multan'];
        foreach ($cities as $city) {
            \App\Models\City::create([
                'name' => $city,
                'slug' => \Illuminate\Support\Str::slug($city),
            ]);
        }

        $cities_models = \App\Models\City::all();
        $cats_models = \App\Models\Category::all();

        foreach($cats_models as $cat) {
            \App\Models\JobListing::create([
                'title' => 'Sample Job for ' . $cat->name,
                'slug' => \Illuminate\Support\Str::slug('Sample Job for ' . $cat->name . '-' . rand(1, 100)),
                'category_id' => $cat->id,
                'city_id' => $cities_models->random()->id,
                'department' => 'HR Department',
                'salary_range' => 'PKR 50k - 80k',
                'deadline' => now()->addDays(15),
                'description_html' => '<p>This is a <strong>sample job</strong> description for testing purposes.</p>',
                'is_featured' => rand(0, 1),
                'is_active' => true,
            ]);
        }
    }
}
