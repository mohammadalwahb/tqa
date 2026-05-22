<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Department;
use App\Models\StaffMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationalRoleController extends Controller
{
    public function index(): View
    {
        $colleges = College::with(['dean', 'departments.head', 'departments.qualityCoordinator'])
            ->orderBy('name_en')
            ->get();

        return view('org_roles.index', compact('colleges'));
    }

    public function updateCollege(Request $request, College $college): RedirectResponse
    {
        $data = $request->validate([
            'dean_staff_id' => ['nullable', 'exists:staff_members,id'],
        ]);

        if (! empty($data['dean_staff_id'])) {
            $staff = StaffMember::find($data['dean_staff_id']);
            if ($staff && $staff->college_id !== $college->id) {
                return back()->with('error', 'Dean must belong to this college.');
            }
        }

        $college->update($data);
        return back()->with('success', 'Dean updated for ' . $college->name_en . '.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'head_staff_id'                => ['nullable', 'exists:staff_members,id'],
            'quality_coordinator_staff_id' => ['nullable', 'exists:staff_members,id'],
        ]);

        foreach (['head_staff_id', 'quality_coordinator_staff_id'] as $field) {
            if (! empty($data[$field])) {
                $staff = StaffMember::find($data[$field]);
                if ($staff && $staff->department_id !== $department->id) {
                    return back()->with('error', "Selected staff must belong to {$department->name_en}.");
                }
            }
        }

        $department->update($data);
        return back()->with('success', "Roles updated for {$department->name_en}.");
    }
}
