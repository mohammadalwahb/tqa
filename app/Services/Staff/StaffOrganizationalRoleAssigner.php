<?php

namespace App\Services\Staff;

use App\Models\College;
use App\Models\Department;
use App\Models\StaffMember;

class StaffOrganizationalRoleAssigner
{
    /** @var list<string> */
    private const DEAN_ALIASES = ['dean', 'dean of college', 'college dean'];

    /** @var list<string> */
    private const HEAD_ALIASES = [
        'head of department',
        'head of dept',
        'department head',
        'hod',
    ];

    public function assignFromPosition(
        StaffMember $staff,
        ?string $position,
        College $college,
        Department $department,
    ): void {
        $normalized = $this->normalize($position);
        if ($normalized === null) {
            return;
        }

        if ($this->matchesAny($normalized, self::DEAN_ALIASES)) {
            if ((int) $staff->college_id === (int) $college->id) {
                $college->update(['dean_staff_id' => $staff->id]);
            }
        }

        if ($this->matchesAny($normalized, self::HEAD_ALIASES)) {
            if ((int) $staff->department_id === (int) $department->id) {
                $department->update(['head_staff_id' => $staff->id]);
            }
        }
    }

    private function normalize(?string $position): ?string
    {
        $position = mb_strtolower(trim((string) $position));

        return $position === '' ? null : $position;
    }

    /**
     * @param  list<string>  $aliases
     */
    private function matchesAny(string $position, array $aliases): bool
    {
        foreach ($aliases as $alias) {
            if ($position === $alias || str_contains($position, $alias)) {
                return true;
            }
        }

        return false;
    }
}
