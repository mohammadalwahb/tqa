<?php

use App\Services\Reporting\ReportColumnCatalog;
use App\Models\StaffMember;

it('exports only the letter grade for derived metrics in csv', function () {
    $catalog = app(ReportColumnCatalog::class);
    $staff = new StaffMember(['full_name_en' => 'Test']);

    $value = $catalog->valueForColumn([
        'staff' => $staff,
        'required' => 1,
        'completed' => 1,
        'percentage' => 100,
        'average' => 4.5,
        'derived_metrics' => [
            7 => [
                'letter_grade' => 'A',
                'letter_range' => '90-100',
                'value' => 95.0,
            ],
        ],
    ], 'metric:7');

    expect($value)->toBe('A');
});
