<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Staff; // Assuming you have a Staff model
use Illuminate\Support\Str;

class StaffTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        // You can also add more staff records by calling the create method again or using a loop to generate multiple records
        // Example for adding multiple staff:
        Staff::factory(10)->create();
    }
}
