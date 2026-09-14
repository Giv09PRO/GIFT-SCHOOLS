<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserProfile; // Ensure to import the model if needed
use Faker\Factory as Faker;

class UserProfilesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Create 10 user profiles with fake data
        foreach (range(1, 10) as $index) {
            UserProfile::create([
                'profile' => $faker->word, // Generating a random word for profile
                'title' => $faker->paragraph, // Generating a random paragraph for title
            ]);
        }
    }
}
