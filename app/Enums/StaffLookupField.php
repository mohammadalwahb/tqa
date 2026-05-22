<?php

namespace App\Enums;

enum StaffLookupField: string
{
    case EmployeeType   = 'employee_type';
    case Qualification  = 'qualification';
    case AcademicTitle  = 'academic_title';
    case Position       = 'position';

    public function label(): string
    {
        return match ($this) {
            self::EmployeeType  => 'Employee type',
            self::Qualification => 'Qualification',
            self::AcademicTitle => 'Academic title',
            self::Position      => 'Position',
        };
    }

    /** @return list<self> */
    public static function casesList(): array
    {
        return self::cases();
    }
}
