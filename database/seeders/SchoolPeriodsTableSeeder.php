<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolPeriod;

class SchoolPeriodsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example seed data
        $schoolPeriods = [
            [
                'syear' => 2024,
                'school_id' => 1,
                'sort_order' => 1,
                'title' => 'Morning Period',
                'short_name' => 'AM',
                'length' => 60, // in minutes
                'start_time' => '08:00',
                'end_time' => '09:00',
                'block' => 'A',
                'attendance' => 'Y',
                'rollover_id' => null,
            ],
            [
                'syear' => 2024,
                'school_id' => 1,
                'sort_order' => 2,
                'title' => 'Midday Period',
                'short_name' => 'PM',
                'length' => 60, // in minutes
                'start_time' => '12:00',
                'end_time' => '13:00',
                'block' => 'B',
                'attendance' => 'Y',
                'rollover_id' => null,
            ],
            [
                'syear' => 2024,
                'school_id' => 1,
                'sort_order' => 3,
                'title' => 'Afternoon Period',
                'short_name' => 'PM2',
                'length' => 60, // in minutes
                'start_time' => '14:00',
                'end_time' => '15:00',
                'block' => 'C',
                'attendance' => 'Y',
                'rollover_id' => null,
            ],
        ];

        // Insert the data
        foreach ($schoolPeriods as $period) {
            SchoolPeriod::create($period);
        }
    }
}
