<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student; // Ensure to import the Student model
use Faker\Factory as Faker;

class StudentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Create 10 students with fake data
        foreach (range(1, 10) as $index) {
            Student::create([
                'last_name' => $faker->lastName,
                'first_name' => $faker->firstName,
                'middle_name' => $faker->optional()->firstName,
                'name_suffix' => $faker->optional()->suffix,
                'username' => $faker->unique()->userName,
                'password' => bcrypt('password'), // You can replace this with a hashed password if needed
                'email' => $faker->optional()->email,
                'last_login' => $faker->optional()->dateTimeThisYear,
                'failed_login' => $faker->optional()->numberBetween(1, 5),
                'prem_number' => $faker->optional()->regexify('[A-Za-z0-9]{12}'),
                'dob' => $faker->optional()->date(),
                'custom_200000003' => $faker->optional()->text,
                'custom_200000004' => $faker->optional()->date(),
                'custom_200000005' => $faker->optional()->text,
                'custom_200000006' => $faker->optional()->text,
                'custom_200000007' => $faker->optional()->text,
                'custom_200000008' => $faker->optional()->text,
                'custom_200000009' => $faker->optional()->text,
                'custom_200000010' => $faker->optional()->randomElement(['Y', 'N']),
                'custom_200000011' => $faker->optional()->text,
            ]);
        }
    }
}
