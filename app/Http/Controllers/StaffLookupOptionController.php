<?php

namespace App\Http\Controllers;

use App\Enums\StaffLookupField;
use App\Http\Requests\StaffLookupOptionRequest;
use App\Models\StaffLookupOption;
use App\Models\StaffMember;
use App\Models\StaffStatus;
use App\Services\Staff\StaffAttributeValidator;
use App\Services\Staff\StaffOptionProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StaffLookupOptionController extends Controller
{
    public function index(): View
    {
        $grouped = [];
        foreach (StaffLookupField::casesList() as $field) {
            $grouped[$field->value] = StaffLookupOption::query()
                ->forField($field)
                ->orderBy('name')
                ->get();
        }

        $statuses = StaffStatus::query()
            ->withCount('staffMembers')
            ->orderBy('name')
            ->get();

        return view('staff_options.index', [
            'grouped'  => $grouped,
            'fields'   => StaffLookupField::casesList(),
            'statuses' => $statuses,
        ]);
    }

    public function create(): View
    {
        $field = StaffLookupField::tryFrom((string) request('field'))
            ?? StaffLookupField::EmployeeType;

        return view('staff_options.form', [
            'option' => new StaffLookupOption(['field' => $field, 'is_active' => true]),
            'fields' => StaffLookupField::casesList(),
        ]);
    }

    public function store(
        StaffLookupOptionRequest $request,
        StaffOptionProvisioningService $provisioner,
    ): RedirectResponse {
        $result = $provisioner->storeLookupOption($request->validated());
        StaffAttributeValidator::flushCache();

        return redirect()
            ->route('staff-options.index')
            ->with('success', $result['restored'] ? 'Option restored.' : 'Option created.');
    }

    public function edit(StaffLookupOption $staff_option): View
    {
        return view('staff_options.form', [
            'option' => $staff_option,
            'fields' => StaffLookupField::casesList(),
        ]);
    }

    public function update(StaffLookupOptionRequest $request, StaffLookupOption $staff_option): RedirectResponse
    {
        $staff_option->update($request->validated());
        StaffAttributeValidator::flushCache();

        return redirect()
            ->route('staff-options.index')
            ->with('success', 'Option updated.');
    }

    public function destroy(StaffLookupOption $staff_option): RedirectResponse
    {
        $inUse = $this->optionInUse($staff_option);
        if ($inUse) {
            return redirect()
                ->route('staff-options.index')
                ->with('error', "Cannot delete \"{$staff_option->name}\" — it is assigned to {$inUse} staff member(s). Deactivate it instead.");
        }

        $staff_option->delete();
        StaffAttributeValidator::flushCache();

        return redirect()
            ->route('staff-options.index')
            ->with('success', 'Option deleted.');
    }

    private function optionInUse(StaffLookupOption $option): int
    {
        $column = $option->field->value;

        return StaffMember::query()->where($column, $option->name)->count();
    }
}
