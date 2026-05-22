<?php

namespace App\Imports;

final class StaffImportTemplate
{
    public const HEADERS = [
        'Full Name (First, Second, Third) in English',
        'Full Name in Kurdish',
        'Institutional e-mail',
        'Gender',
        'Date of Birth',
        'Age',
        'Employee type',
        'College',
        'Department',
        'Qualification',
        'Academic Title',
        'Position',
        'Status',
    ];

    /**
     * @return list<string>
     */
    public static function sampleRow(): array
    {
        return [
            'Ahmed Ali Mohammed',
            'ئەحمەد عەلی محەمەد',
            'ahmed.ali@uoz.edu.krd',
            'male',
            '1985-01-15',
            '40',
            'Permanent',
            'College of Science',
            'Computer Science',
            'PhD',
            'Assistant Professor',
            'Lecturer',
            'Active',
        ];
    }

    /**
     * @return list<list<string>>
     */
    public static function rows(): array
    {
        return [self::sampleRow()];
    }
}
