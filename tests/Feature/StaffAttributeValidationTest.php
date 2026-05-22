<?php

use App\Imports\StaffImportTemplate;
use App\Models\College;
use App\Models\Department;
use App\Models\StaffMember;
use App\Models\StaffLookupOption;
use App\Models\User;
use App\Enums\StaffLookupField;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('rejects staff create when employee type is not configured', function () {
    $admin = User::role('Super Admin')->firstOrFail();
    $college = College::query()->firstOrFail();
    $department = Department::query()->where('college_id', $college->id)->firstOrFail();

    $response = $this->actingAs($admin)->post(route('staff.store'), [
        'full_name_en'      => 'Test User',
        'email'             => 'test.user@uoz.edu.krd',
        'college_id'        => $college->id,
        'department_id'     => $department->id,
        'employee_type'     => 'Not Allowed Type',
        'is_teaching_staff' => true,
        'is_active'         => true,
    ]);

    $response->assertSessionHasErrors('employee_type');
});

it('rejects csv import with invalid status name', function () {
    $admin = User::role('Super Admin')->firstOrFail();

    $college = College::firstOrCreate(['name_en' => 'College of Science'], ['is_active' => true]);
    Department::firstOrCreate(
        ['college_id' => $college->id, 'name_en' => 'Computer Science'],
        ['is_active' => true]
    );

    $row = StaffImportTemplate::sampleRow();
    $row[12] = 'Unknown Status';

    $csv = implode(',', array_map(
        fn ($v) => '"' . str_replace('"', '""', $v) . '"',
        StaffImportTemplate::HEADERS
    )) . "\n" . implode(',', array_map(
        fn ($v) => '"' . str_replace('"', '""', $v) . '"',
        $row
    ));

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('staff.csv', $csv);

    $response = $this->actingAs($admin)->post(route('staff.import'), ['file' => $file]);

    $response->assertRedirect(route('staff.index'));
    $response->assertSessionHas('error');
    expect(StaffMember::where('email', 'ahmed.ali@uoz.edu.krd')->exists())->toBeFalse();
});

it('allows staff create with configured lookup values', function () {
    $admin = User::role('Super Admin')->firstOrFail();
    $college = College::query()->firstOrFail();
    $department = Department::query()->where('college_id', $college->id)->firstOrFail();

    $employeeType = StaffLookupOption::query()
        ->forField(StaffLookupField::EmployeeType)
        ->active()
        ->value('name');

    $response = $this->actingAs($admin)->post(route('staff.store'), [
        'full_name_en'      => 'Valid Staff',
        'email'             => 'valid.staff@uoz.edu.krd',
        'college_id'        => $college->id,
        'department_id'     => $department->id,
        'employee_type'     => $employeeType,
        'is_teaching_staff' => true,
        'is_active'         => true,
    ]);

    $response->assertRedirect(route('staff.index'));
    $response->assertSessionHas('success');

    expect(StaffMember::where('email', 'valid.staff@uoz.edu.krd')->value('employee_type'))
        ->toBe($employeeType);
});
