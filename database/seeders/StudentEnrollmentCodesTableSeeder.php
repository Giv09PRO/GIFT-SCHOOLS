<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StudentEnrollmentCode;  // Ensure you import the StudentEnrollmentCode model
use Faker\Factory as Faker;

class StudentEnrollmentCodesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Create 10 student enrollment codes with fake data
        foreach (range(1, 10) as $index) {
            StudentEnrollmentCode::create([
                'syear' => $faker->year(), // Random year (e.g., 2024)
                'title' => $faker->word(), // Random title (e.g., 'New Enrollment')
                'short_name' => $faker->word(), // Random short name (e.g., 'NE')
                'type' => $faker->randomElement(['A', 'B', 'C', 'D']), // Random type (e.g., 'A')
                'default_code' => $faker->randomElement(['Y', 'N']), // Random default code ('Y' or 'N')
                'sort_order' => $faker->numberBetween(1, 100), // Random sort order
            ]);
        }
    }
}
