<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StudentEnrollment;  // Ensure you import the StudentEnrollment model
use App\Models\School;            // Import the School model
use App\Models\Student;           // Import the Student model
use Faker\Factory as Faker;

class StudentEnrollmentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get the first school (you can change this logic as needed)
        $school = School::first(); // Or use School::inRandomOrder()->first() for a random school

        // Get the first student (you can change this logic as needed)
        $student = Student::first(); // Or use Student::inRandomOrder()->first() for a random student

        // Create 10 student enrollments with fake data
        foreach (range(1, 10) as $index) {
            StudentEnrollment::create([
                'syear' => ('2024'), // Random year (e.g., 2024)
                'school_id' => (1), // Foreign key to the school
                'student_id' => $student->id, // Foreign key to the student
                'grade_id' => $faker->numberBetween(1, 12), // Random grade ID
                'start_date' => $faker->date(), // Random start date
                'end_date' => $faker->optional()->date(), // Optional end date
                'enrollment_code' => $faker->numberBetween(1, 5), // Random enrollment code
                'drop_code' => $faker->optional()->numberBetween(1, 5), // Optional drop code
                'next_school' => $faker->optional()->numberBetween(1, 10), // Optional next school
                'calendar_id' => $faker->numberBetween(1, 5), // Random calendar ID
                'last_school' => $faker->numberBetween(1, 10), // Random last school
            ]);
        }
    }
}
