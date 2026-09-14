<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject; // Ensure you import the Subject model
use App\Models\School;  // Import the School model to get an existing school
use Faker\Factory as Faker;

class SubjectsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get the first school (or change this logic as needed)
        $school = School::first(); // You can adjust this to get a random school if needed

        // Create 10 subjects with fake data
        foreach (range(1, 10) as $index) {
            Subject::create([
                'syear' => ('2024'),  // Random year between 1900 and 2099
                'school_id' => ('1'), // Foreign key from the school table
                'title' => $faker->word,  // Random word for subject title
                'short_name' => $faker->optional()->word, // Optional short name for subject
                'sort_order' => $faker->optional()->randomNumber(), // Optional sort order
                'rollover_id' => $faker->optional()->randomNumber(), // Optional rollover ID
            ]);
        }
    }
}
