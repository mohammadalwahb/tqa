<?php

use App\Imports\StaffImportTemplate;
use App\Models\College;
use App\Models\Department;
use App\Models\StaffMember;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('assigns dean and head of department from csv position on import', function () {
    $admin = User::role('Super Admin')->firstOrFail();

    $college = College::firstOrCreate(['name_en' => 'College of Science'], ['is_active' => true]);
    $department = Department::firstOrCreate(
        ['college_id' => $college->id, 'name_en' => 'Computer Science'],
        ['is_active' => true]
    );

    $deanRow = StaffImportTemplate::sampleRow();
    $deanRow[0] = 'Dean Person';
    $deanRow[1] = '';
    $deanRow[2] = 'dean.person@uoz.edu.krd';
    $deanRow[11] = 'Dean';

    $headRow = StaffImportTemplate::sampleRow();
    $headRow[0] = 'Head Person';
    $headRow[1] = '';
    $headRow[2] = 'head.person@uoz.edu.krd';
    $headRow[11] = 'Head of Department';

    $csv = "\xEF\xBB\xBF" . implode(',', array_map(
        fn ($v) => '"' . str_replace('"', '""', $v) . '"',
        StaffImportTemplate::HEADERS
    )) . "\n" . implode(',', array_map(
        fn ($v) => '"' . str_replace('"', '""', $v) . '"',
        $deanRow
    )) . "\n" . implode(',', array_map(
        fn ($v) => '"' . str_replace('"', '""', $v) . '"',
        $headRow
    ));

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('staff-roles.csv', $csv);

    $this->actingAs($admin)->post(route('staff.import'), ['file' => $file])->assertRedirect(route('staff.index'));

    $deanStaff = StaffMember::where('email', 'dean.person@uoz.edu.krd')->firstOrFail();
    $headStaff = StaffMember::where('email', 'head.person@uoz.edu.krd')->firstOrFail();

    expect($college->fresh()->dean_staff_id)->toBe($deanStaff->id);
    expect($department->fresh()->head_staff_id)->toBe($headStaff->id);
});
