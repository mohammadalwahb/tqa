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
        return \App\Support\LocaleHelper::staffFieldLabel($this->value);
    }

    /** @return list<self> */
    public static function casesList(): array
    {
        return self::cases();
    }
}
