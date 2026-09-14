<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition()
    {
        return [
            'syear' => $this->faker->year(),
            'current_school_id' => (1),
            'title' => $this->faker->title(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'middle_name' => $this->faker->optional()->firstName(),
            'name_suffix' => $this->faker->optional()->suffix(),
            'username' => $this->faker->userName(),
            'password' => Hash::make('password123'), // You can change the default password here
            'email' => $this->faker->email(),
            'custom_200000001' => $this->faker->text(),
            'current_school_syear' => ('2024'),
            'profile' => $this->faker->word(),
            'schools' => $this->faker->company(),
            'last_login' => $this->faker->dateTimeThisYear(),
            'failed_login' => $this->faker->numberBetween(0, 5),
            'profile_id' => $this->faker->numberBetween(1, 10),
            'rollover_id' => $this->faker->numberBetween(1, 10),
        ];
    }
}
