<?php

namespace App\Support;

use App\Models\College;
use App\Models\Department;
use App\Models\StaffMember;

class LocaleHelper
{
    /** @var list<string> */
    public const SUPPORTED = ['en', 'ar', 'ku'];

    public static function current(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, self::SUPPORTED, true) ? $locale : 'en';
    }

    public static function isRtl(?string $locale = null): bool
    {
        return in_array($locale ?? self::current(), ['ar', 'ku'], true);
    }

    public static function direction(?string $locale = null): string
    {
        return self::isRtl($locale) ? 'rtl' : 'ltr';
    }

    public static function staffDisplayName(StaffMember $staff): string
    {
        return match (self::current()) {
            'ku', 'ar' => $staff->full_name_ku ?: $staff->full_name_en,
            default => $staff->full_name_en,
        };
    }

    public static function departmentDisplayName(?Department $department): string
    {
        if (! $department) {
            return '';
        }

        return match (self::current()) {
            'ku', 'ar' => $department->name_ku ?: $department->name_en,
            default => $department->name_en,
        };
    }

    public static function collegeDisplayName(?College $college): string
    {
        if (! $college) {
            return '';
        }

        return match (self::current()) {
            'ku', 'ar' => $college->name_ku ?: $college->name_en,
            default => $college->name_en,
        };
    }

    public static function enum(string $group, ?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $key = "enums.{$group}.{$value}";
        $translated = __($key);

        return $translated === $key ? $value : $translated;
    }

    public static function staffFieldLabel(string $field): string
    {
        $key = "fields.{$field}";
        $translated = __($key);

        return $translated === $key ? $field : $translated;
    }

    public static function roleDisplayName(string $roleName): string
    {
        $key = match ($roleName) {
            'Super Admin' => 'roles.super_admin',
            'Quality College Coordinator' => 'roles.quality_coordinator',
            'Local Committee Member' => 'roles.local_committee',
            'HD Committee Member' => 'roles.hd_committee',
            'Department Head' => 'roles.department_head',
            default => null,
        };

        return $key ? __($key) : $roleName;
    }
}
