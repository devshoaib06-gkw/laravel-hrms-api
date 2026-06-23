<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'designation_id' => Designation::factory(),
            'phone' => fake()->phoneNumber(),
            'joining_date' => fake()->date(),
            'salary' => fake()->randomFloat(2, 30000, 90000),
            'status' => 'active',
        ];
    }
}
