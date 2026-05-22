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

it('downloads a csv template with the required columns', function () {
    $admin = User::role('Super Admin')->firstOrFail();

    $response = $this->actingAs($admin)->get(route('staff.template'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $content = preg_replace('/^\xEF\xBB\xBF/', '', $response->streamedContent());
    $lines   = array_map('str_getcsv', explode("\n", trim($content)));
    expect($lines[0])->toBe(StaffImportTemplate::HEADERS);
});

it('imports staff using the new template column names', function () {
    $admin = User::role('Super Admin')->firstOrFail();

    $college = College::firstOrCreate(['name_en' => 'College of Science'], ['is_active' => true]);
    Department::firstOrCreate(
        ['college_id' => $college->id, 'name_en' => 'Computer Science'],
        ['is_active' => true]
    );

    $csv = implode(',', array_map(
        fn ($v) => '"' . str_replace('"', '""', $v) . '"',
        StaffImportTemplate::HEADERS
    )) . "\n" . implode(',', array_map(
        fn ($v) => '"' . str_replace('"', '""', $v) . '"',
        StaffImportTemplate::sampleRow()
    ));

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('staff.csv', $csv);

    $response = $this->actingAs($admin)->post(route('staff.import'), ['file' => $file]);

    $response->assertRedirect(route('staff.index'));
    $response->assertSessionHas('success');

    $staff = StaffMember::where('email', 'ahmed.ali@uoz.edu.krd')->first();
    expect($staff)->not->toBeNull();
    expect($staff->full_name_en)->toBe('Ahmed Ali Mohammed');
});
