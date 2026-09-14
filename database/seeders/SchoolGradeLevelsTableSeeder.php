<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GradeLevel;  // Ensure you import the model
use Faker\Factory as Faker;

class SchoolGradeLevelsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Create 10 school grade levels with fake data
        foreach (range(1, 10) as $index) {
            GradeLevel::create([
                'school_id' =>(1), // Random school_id (adjust as needed)
                'school_syear' => ('2024'), // Random school year
                'short_name' => $faker->word(), // Random short name (e.g., 'G1')
                'title' => $faker->word(), // Random title (e.g., 'Grade 1')
                'next_grade_id' => $faker->numberBetween(1, 10), // Random next grade level (nullable)
                'sort_order' => $faker->numberBetween(1, 10), // Random sort order (optional)
            ]);
        }
    }
}
