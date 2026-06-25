<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin to list departments', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Department::factory()->count(3)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/departments')
        ->assertStatus(200)
        ->assertJsonCount(3);
});

it('allows admin to create a department', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/departments', ['name' => 'Engineering'])
        ->assertStatus(201)
        ->assertJsonFragment(['name' => 'Engineering']);
});

it('fails validation when department name is missing', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/departments', [])
        ->assertStatus(422);
});

it('fails validation when department name is duplicate', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Department::factory()->create(['name' => 'Engineering']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/departments', ['name' => 'Engineering'])
        ->assertStatus(422);
});

it('allows admin to delete a department', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $department = Department::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/departments/{$department->id}")
        ->assertStatus(200);

    $this->assertSoftDeleted('departments', ['id' => $department->id]);
});
