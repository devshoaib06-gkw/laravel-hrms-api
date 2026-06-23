<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function actingAsEmployee(): Employee
{
    $user = User::factory()->create(['role' => 'employee']);
    $employee = Employee::factory()->for($user)->create();

    test()->actingAs($user);

    return $employee;
}

test('employee can check in', function () {
    actingAsEmployee();

    $response = $this->postJson('/api/v1/attendance/check-in');

    $response->assertCreated();
    $this->assertDatabaseHas('attendances', [
        'status' => 'present',
    ]);
});

test('employee cannot check in twice same day', function () {
    actingAsEmployee();

    $this->postJson('/api/v1/attendance/check-in')->assertCreated();
    $response = $this->postJson('/api/v1/attendance/check-in');

    $response->assertStatus(422);
    $this->assertDatabaseCount('attendances', 1);
});

test('employee can check out after check in', function () {
    actingAsEmployee();

    $this->postJson('/api/v1/attendance/check-in')->assertCreated();
    $response = $this->postJson('/api/v1/attendance/check-out');

    $response->assertSuccessful();
    $response->assertJsonPath('clock_out', fn ($value) => $value !== null);
});

test('employee cannot check out without check in', function () {
    actingAsEmployee();

    $response = $this->postJson('/api/v1/attendance/check-out');

    $response->assertStatus(422);
});

test('employee cannot check out twice same day', function () {
    actingAsEmployee();

    $this->postJson('/api/v1/attendance/check-in')->assertCreated();
    $this->postJson('/api/v1/attendance/check-out')->assertSuccessful();
    $response = $this->postJson('/api/v1/attendance/check-out');

    $response->assertStatus(422);
});

test('employee sees only own attendance in index', function () {
    $employee = actingAsEmployee();
    $employee->attendances()->create([
        'date' => now()->toDateString(),
        'status' => 'present',
    ]);

    $other = Employee::factory()->create();
    $other->attendances()->create([
        'date' => now()->toDateString(),
        'status' => 'present',
    ]);

    $response = $this->getJson('/api/v1/attendance');

    $response->assertSuccessful();
    $response->assertJsonCount(1);
});

test('unauthenticated user cannot access attendance', function () {
    $response = $this->getJson('/api/v1/attendance');

    $response->assertUnauthorized();
});
