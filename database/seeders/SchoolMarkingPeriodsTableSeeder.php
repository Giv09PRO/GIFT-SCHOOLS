<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolMarkingPeriod; // Assuming you have a SchoolMarkingPeriod model

class SchoolMarkingPeriodsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example of creating a school marking period record
        SchoolMarkingPeriod::create([
            'syear' => 2024,
            'mp' => 'MP1', // Marking Period code
            'school_id' => 1, // Assuming this is a valid school ID
            'title' => 'First Marking Period',
            'short_name' => 'MP1',
            'sort_order' => 1,
            'start_date' => '2024-09-01',
            'end_date' => '2024-11-30',
            'post_start_date' => '2024-12-01',
            'post_end_date' => '2024-12-15',
            'does_grades' => 'Y', // Does this marking period have grades
            'does_comments' => 'Y', // Does this marking period have comments
            'rollover_id' => null, // Assuming no rollover ID initially
        ]);


    }
}
