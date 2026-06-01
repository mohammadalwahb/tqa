<?php

namespace App\Services\Committees;

use App\Models\Department;
use App\Models\StaffMember;
use App\Support\LocaleHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CommitteeStaffOptionsService
{
    /**
     * @return array{items: array<int, array{id:int,label:string}>, university_wide: bool}
     */
    public function optionsForRequest(Request $request): array
    {
        $request->validate([
            'college_id'            => ['required', 'exists:colleges,id'],
            'department_id'         => ['nullable', 'exists:departments,id'],
            'exclude_department_id' => ['nullable', 'exists:departments,id'],
            'exclude_head'          => ['nullable'],
            'filter'                => ['nullable', 'in:department,college,other'],
        ]);

        $collegeId = (int) $request->college_id;
        $universityWide = false;

        $query = StaffMember::query()
            ->where('is_active', true)
            ->where('college_id', $collegeId)
            ->orderBy('full_name_en');

        if ($request->input('filter') === 'department' && $request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        } elseif ($request->input('filter') === 'college') {
            // All staff in the college (department_id only used to exclude head).
        } elseif ($request->input('filter') === 'other' && $request->filled('exclude_department_id')) {
            $query->where('department_id', '<>', $request->exclude_department_id);
        } elseif ($request->filled('exclude_department_id')) {
            $query->where('department_id', '<>', $request->exclude_department_id);
        } elseif ($request->filled('department_id') && $request->input('filter') !== 'college') {
            $query->where('department_id', $request->department_id);
        }

        if ($request->boolean('exclude_head') && $request->filled('department_id')) {
            $headId = Department::whereKey($request->department_id)->value('head_staff_id');
            if ($headId) {
                $query->where('id', '<>', $headId);
            }
        }

        $rows = $query->with(['department.college'])->get();

        if ($rows->isEmpty() && $this->shouldOfferUniversityWidePool($request, $collegeId)) {
            $rows = $this->universityStaffQuery($request)->get();
            $universityWide = true;
        }

        return [
            'items'           => $this->formatStaffOptions($rows),
            'university_wide' => $universityWide,
        ];
    }

    public function collegeHasSingleDepartment(int $collegeId): bool
    {
        return Department::query()
            ->where('college_id', $collegeId)
            ->where('is_active', true)
            ->count() <= 1;
    }

    private function shouldOfferUniversityWidePool(Request $request, int $collegeId): bool
    {
        if ($request->input('filter') === 'other' || $request->filled('exclude_department_id')) {
            return $this->collegeHasSingleDepartment($collegeId);
        }

        return false;
    }

    private function universityStaffQuery(Request $request)
    {
        $query = StaffMember::query()
            ->where('is_active', true)
            ->orderBy('full_name_en');

        if ($request->filled('exclude_department_id')) {
            $query->where('department_id', '<>', $request->exclude_department_id);
        }

        if ($request->boolean('exclude_head') && $request->filled('department_id')) {
            $headId = Department::whereKey($request->department_id)->value('head_staff_id');
            if ($headId) {
                $query->where('id', '<>', $headId);
            }
        }

        return $query->with(['department.college']);
    }

    /**
     * @param  Collection<int, StaffMember>  $rows
     * @return array<int, array{id:int,label:string}>
     */
    private function formatStaffOptions(Collection $rows): array
    {
        return $rows->map(function (StaffMember $staff) {
            $deptLabel = LocaleHelper::departmentDisplayName($staff->department);
            $collegeLabel = LocaleHelper::collegeDisplayName($staff->department?->college);
            $suffix = collect([$deptLabel, $collegeLabel])->filter()->unique()->join(' · ');

            return [
                'id'    => $staff->id,
                'label' => LocaleHelper::staffDisplayName($staff) . ($suffix ? ' · ' . $suffix : ''),
            ];
        })->values()->all();
    }
}
