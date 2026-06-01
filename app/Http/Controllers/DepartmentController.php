<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\College;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::with('college')->withCount('staffMembers')->latest()->get();

        return view('departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('departments.form', [
            'department' => new Department(),
            'colleges'   => College::orderBy('name_en')->get(),
        ]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        Department::create($request->validated());

        return redirect()->route('departments.index')->with('success', __('messages.department_created'));
    }

    public function edit(Department $department): View
    {
        return view('departments.form', [
            'department' => $department,
            'colleges'   => College::orderBy('name_en')->get(),
        ]);
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return redirect()->route('departments.index')->with('success', __('messages.department_updated'));
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()->route('departments.index')->with('success', __('messages.department_deleted'));
    }
}
