<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Leave>
 */
class LeaveFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fromDate = fake()->dateTimeBetween('-1 month', '+1 month');
        $toDate = fake()->dateTimeBetween($fromDate, (clone $fromDate)->modify('+7 days'));

        return [
            'employee_id' => Employee::factory(),
            'type' => fake()->randomElement(['annual', 'sick', 'unpaid']),
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'reason' => fake()->sentence(),
            'status' => 'pending',
        ];
    }
}
