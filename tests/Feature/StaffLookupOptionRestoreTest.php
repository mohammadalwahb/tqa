<?php

use App\Enums\StaffLookupField;
use App\Models\StaffLookupOption;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('restores a soft-deleted staff lookup option when adding the same name again', function () {
    $admin = User::role('Super Admin')->firstOrFail();

    $option = StaffLookupOption::query()
        ->forField(StaffLookupField::EmployeeType)
        ->where('name', 'Permanent')
        ->firstOrFail();

    $option->delete();
    expect(StaffLookupOption::query()->where('name', 'Permanent')->exists())->toBeFalse();

    $response = $this->actingAs($admin)->post(route('staff-options.store'), [
        'field'     => StaffLookupField::EmployeeType->value,
        'name'      => 'Permanent',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('staff-options.index'));
    $response->assertSessionHas('success', 'Option restored.');

    $restored = StaffLookupOption::query()->where('name', 'Permanent')->first();
    expect($restored)->not->toBeNull();
    expect($restored->trashed())->toBeFalse();
});
