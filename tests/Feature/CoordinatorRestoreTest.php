<?php

use App\Models\College;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('restores a soft-deleted user when creating a coordinator with the same email', function () {
    $admin   = User::role('Super Admin')->firstOrFail();
    $college = College::query()->firstOrFail();

    $email = 'restore-coord-test@uoz.edu.krd';
    $user  = User::factory()->create([
        'email'      => $email,
        'college_id' => $college->id,
    ]);
    $user->delete();
    expect($user->fresh()->trashed())->toBeTrue();

    $response = $this->actingAs($admin)->post(route('coordinators.store'), [
        'name'       => 'Restored Coordinator',
        'email'      => $email,
        'college_id' => $college->id,
        'is_active'  => true,
    ]);

    $response->assertRedirect(route('coordinators.index'));
    $response->assertSessionHas('success');

    $restored = User::query()->where('email', $email)->firstOrFail();
    expect($restored->trashed())->toBeFalse();
    expect($restored->name)->toBe('Restored Coordinator');
    expect($restored->hasRole('Quality College Coordinator'))->toBeTrue();
});
