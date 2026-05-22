<?php

use App\Imports\StaffImportTemplate;
use App\Models\StaffMember;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('imports kurdish full names from a utf8 csv', function () {
    $admin = User::role('Super Admin')->firstOrFail();
    $kurdish = 'ئەحمەد عەلی محەمەد';

    $row = StaffImportTemplate::sampleRow();
    $row[1] = $kurdish;

    $csv = "\xEF\xBB\xBF" . implode(',', array_map(
        fn ($v) => '"' . str_replace('"', '""', $v) . '"',
        StaffImportTemplate::HEADERS
    )) . "\n" . implode(',', array_map(
        fn ($v) => '"' . str_replace('"', '""', $v) . '"',
        $row
    ));

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('staff-utf8.csv', $csv);

    $this->actingAs($admin)
        ->post(route('staff.import'), ['file' => $file])
        ->assertRedirect(route('staff.index'));

    expect(StaffMember::where('email', 'ahmed.ali@uoz.edu.krd')->value('full_name_ku'))
        ->toBe($kurdish);
});
