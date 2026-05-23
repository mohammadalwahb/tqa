<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('allows super admin to create another super admin', function () {
    $admin = User::role(RolePermissionSeeder::ROLE_SUPER_ADMIN)->firstOrFail();

    $response = $this->actingAs($admin)->post(route('super-admins.store'), [
        'name'      => 'Second Admin',
        'email'     => 'second-admin@uoz.edu.krd',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('super-admins.index'));
    $response->assertSessionHas('success');

    $created = User::query()->where('email', 'second-admin@uoz.edu.krd')->firstOrFail();
    expect($created->hasRole(RolePermissionSeeder::ROLE_SUPER_ADMIN))->toBeTrue();
});

it('denies non super admin from super admin management', function () {
    $coord = User::factory()->create([
        'email'      => 'coord-denied@uoz.edu.krd',
        'is_active'  => true,
    ]);
    $coord->assignRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR);

    $this->actingAs($coord)
        ->get(route('super-admins.index'))
        ->assertStatus(403);
});
