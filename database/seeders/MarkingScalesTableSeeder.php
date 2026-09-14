<?php

namespace Database\Seeders;

use App\Models\MarkingScale;
use Illuminate\Database\Seeder;

class MarkingScalesTableSeeder extends Seeder
{
    public function run()
    {
        $scales = [
            ['syear' => 2024, 'school_id' => 1, 'name' => 'Standard Scale', 'min_score' => 90, 'max_score' => 100, 'letter_grade' => 'A', 'comment' => 'Excellent'],
            ['syear' => 2024, 'school_id' => 1, 'name' => 'Standard Scale', 'min_score' => 81, 'max_score' => 89.99, 'letter_grade' => 'A', 'comment' => 'Very Good'],
            ['syear' => 2024, 'school_id' => 1, 'name' => 'Standard Scale', 'min_score' => 61, 'max_score' => 80.99, 'letter_grade' => 'B', 'comment' => ' Very Good'],
            ['syear' => 2024, 'school_id' => 1, 'name' => 'Standard Scale', 'min_score' => 41, 'max_score' => 60.99, 'letter_grade' => 'C', 'comment' => 'Average'],
            ['syear' => 2024, 'school_id' => 1, 'name' => 'Standard Scale', 'min_score' => 21, 'max_score' => 40.99, 'letter_grade' => 'D', 'comment' => 'Satisfactory'],
            ['syear' => 2024, 'school_id' => 1, 'name' => 'Standard Scale', 'min_score' => 0, 'max_score' => 20.99, 'letter_grade' => 'F', 'comment' => 'Fail'],
        ];

        foreach ($scales as $scale) {
            MarkingScale::create($scale);
        }
    }
}
