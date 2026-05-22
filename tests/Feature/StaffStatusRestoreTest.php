<?php

use App\Models\StaffStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('restores a soft-deleted staff status when adding the same name again', function () {
    $admin = User::role('Super Admin')->firstOrFail();

    $status = StaffStatus::query()->whereNameInsensitive('Retired')->firstOrFail();
    $status->delete();

    expect(StaffStatus::query()->whereNameInsensitive('Retired')->exists())->toBeFalse();

    $response = $this->actingAs($admin)->post(route('staff-statuses.store'), [
        'name'      => 'Retired',
        'color'     => 'secondary',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('staff-options.index'));
    $response->assertSessionHas('success', 'Staff status restored.');

    expect(StaffStatus::query()->whereNameInsensitive('Retired')->exists())->toBeTrue();
});

it('restores a soft-deleted status when the name casing differs', function () {
    $admin = User::role('Super Admin')->firstOrFail();

    StaffStatus::query()->whereNameInsensitive('Retired')->firstOrFail()->delete();

    $response = $this->actingAs($admin)->post(route('staff-statuses.store'), [
        'name'      => 'retired',
        'color'     => 'dark',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('staff-options.index'));
    $response->assertSessionHas('success', 'Staff status restored.');

    expect(StaffStatus::query()->whereNameInsensitive('retired')->count())->toBe(1);
});

it('rejects creating a status that is still on the active list', function () {
    $admin = User::role('Super Admin')->firstOrFail();

    $response = $this->actingAs($admin)->post(route('staff-statuses.store'), [
        'name'      => 'Active',
        'color'     => 'success',
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors('name');
});
