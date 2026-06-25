<?php

// tests/Feature/LeaveTest.php

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helper to create employee with user
function createEmployeeUser(string $role = 'employee'): User
{
    $user = User::factory()->create(['role' => $role]);
    $department = Department::factory()->create();
    $designation = Designation::factory()->create([
        'department_id' => $department->id,
    ]);
    Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'designation_id' => $designation->id,
    ]);

    return $user;
}

it('allows employee to apply for leave', function () {
    $user = createEmployeeUser('employee');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/leaves', [
            'type' => 'sick',
            'from_date' => '2025-07-01',
            'to_date' => '2025-07-03',
            'reason' => 'Not feeling well',
        ])
        ->assertStatus(201)
        ->assertJsonFragment(['status' => 'pending']);
});

it('auto calculates total days on leave application', function () {
    $user = createEmployeeUser('employee');

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/leaves', [
            'type' => 'sick',
            'from_date' => '2025-07-01',
            'to_date' => '2025-07-05',
            'reason' => 'Family trip',
        ])
        ->assertStatus(201);

    expect($response['total_days'])->toBe(5);
});

it('allows hr_manager to approve leave', function () {
    $hr = createEmployeeUser('hr_manager');
    $user = createEmployeeUser('employee');

    $leave = Leave::factory()->create([
        'employee_id' => $user->employee->id,
        'status' => 'pending',
    ]);

    $this->actingAs($hr, 'sanctum')
        ->putJson("/api/v1/leaves/{$leave->id}", [
            'status' => 'approved',
        ])
        ->assertStatus(200)
        ->assertJsonFragment(['status' => 'approved']);
});

it('blocks employee from approving leave', function () {
    $user = createEmployeeUser('employee');
    $leave = Leave::factory()->create([
        'employee_id' => $user->employee->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/leaves/{$leave->id}", [
            'status' => 'approved'
        ])
        ->assertStatus(403);
});
