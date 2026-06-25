<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── DEPARTMENTS (admin only) ──

it('allows admin to access departments', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/departments')
        ->assertStatus(200);
});

it('blocks hr_manager from accessing departments', function () {
    $hr = User::factory()->create(['role' => 'hr_manager']);

    $this->actingAs($hr, 'sanctum')
        ->getJson('/api/v1/departments')
        ->assertStatus(403);
});

it('blocks employee from accessing departments', function () {
    $employee = User::factory()->create(['role' => 'employee']);

    $this->actingAs($employee, 'sanctum')
        ->getJson('/api/v1/departments')
        ->assertStatus(403);
});

// ── EMPLOYEES (admin + hr_manager) ──

it('allows admin to access employees', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/employees')
        ->assertStatus(200);
});

it('allows hr_manager to access employees', function () {
    $hr = User::factory()->create(['role' => 'hr_manager']);

    $this->actingAs($hr, 'sanctum')
        ->getJson('/api/v1/employees')
        ->assertStatus(200);
});

it('blocks employee from accessing employees', function () {
    $employee = User::factory()->create(['role' => 'employee']);

    $this->actingAs($employee, 'sanctum')
        ->getJson('/api/v1/employees')
        ->assertStatus(403);
});

// ── PAYROLL (admin + hr_manager) ──

it('allows admin to access payroll', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/payroll')
        ->assertStatus(200);
});

it('allows hr_manager to access payroll', function () {
    $hr = User::factory()->create(['role' => 'hr_manager']);

    $this->actingAs($hr, 'sanctum')
        ->getJson('/api/v1/payroll')
        ->assertStatus(200);
});

it('blocks employee from accessing payroll', function () {
    $employee = User::factory()->create(['role' => 'employee']);

    $this->actingAs($employee, 'sanctum')
        ->getJson('/api/v1/payroll')
        ->assertStatus(403);
});

// ── LEAVES (all authenticated) ──

it('allows employee to access leaves', function () {
    $employee = User::factory()->create(['role' => 'employee']);
    \App\Models\Employee::factory()->create(['user_id' => $employee->id]);

    $this->actingAs($employee, 'sanctum')
        ->getJson('/api/v1/leaves')
        ->assertStatus(200);
});

it('blocks unauthenticated user from accessing leaves', function () {
    $this->getJson('/api/v1/leaves')
        ->assertStatus(401);
});

// ── ATTENDANCE (all authenticated) ──

it('allows employee to access attendance', function () {
    $employee = User::factory()->create(['role' => 'employee']);
    \App\Models\Employee::factory()->create(['user_id' => $employee->id]);

    $this->actingAs($employee, 'sanctum')
        ->getJson('/api/v1/attendance')
        ->assertStatus(200);
});

it('blocks unauthenticated user from accessing attendance', function () {
    $this->getJson('/api/v1/attendance')
        ->assertStatus(401);
});