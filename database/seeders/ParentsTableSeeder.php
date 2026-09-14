<?php

namespace Database\Seeders;

use App\Models\Parents; // Assuming the model is named Parent (avoid naming conflicts with PHP's built-in Parent class)
use App\Models\Student; // Assuming you have a Student model set up
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ParentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create sample parents
        $parent1 = Parents::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => 'Edward',
            'name_suffix' => 'Sr',
            'gender' => 'Male',
            'name_prefix' => 'Mr.',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
            'username' => 'johndoe',
            'password' => Hash::make('password123'), // Use Hash::make for hashing passwords
            'last_login' => now(),
            'failed_login' => 0,
        ]);

        $parent2 = Parents::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'middle_name' => 'Ann',
            'name_suffix' => null,
            'gender' => 'Female',
            'name_prefix' => 'Mrs.',
            'email' => 'jane.smith@example.com',
            'phone' => '0987654321',
            'username' => 'janesmith',
            'password' => Hash::make('password123'),
            'last_login' => now(),
            'failed_login' => 0,
        ]);

        // Assuming you have students with IDs 1 and 2
        $student1 = Student::find(1);
        $student2 = Student::find(2);

        // Attach parents to students
        $parent1->students()->attach($student1->id, ['relationship' => 'Father']);
        $parent2->students()->attach($student2->id, ['relationship' => 'Mother']);
    }
}
