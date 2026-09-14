<?php

namespace Database\Seeders;

use App\Models\Student;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        $this->call([
//            StudentsTableSeeder::class,
//            StaffTableSeeder::class,
//            SchoolGradeLevelsTableSeeder::class,
//            StudentEnrollmentCodesTableSeeder::class,
            StudentEnrollmentTableSeeder::class,
//            StaffExceptionsTableSeeder::class,
            SubjectsTableSeeder::class,
//            SchoolMarkingPeriodsTableSeeder::class,
            SchoolPeriodsTableSeeder::class,
//            BillingFeesTableSeeder::class,
//            BillingPaymentsTableSeeder::class,
            ExamsTableSeeder::class,
//            ResultsTableSeeder::class,
//            ReportCardsTableSeeder::class,
//            MarkingScalesTableSeeder::class,
            UserProfilesTableSeeder::class,
//            ParentsTableSeeder::class,
        ]);
    }

}
