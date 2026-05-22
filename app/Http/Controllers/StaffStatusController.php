<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffStatusRequest;
use App\Models\StaffStatus;
use App\Services\Staff\StaffAttributeValidator;
use App\Services\Staff\StaffOptionProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StaffStatusController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('staff-options.index');
    }

    public function create(): View
    {
        return view('staff_statuses.form', ['status' => new StaffStatus()]);
    }

    public function store(
        StaffStatusRequest $request,
        StaffOptionProvisioningService $provisioner,
    ): RedirectResponse {
        $result = $provisioner->storeStatus($request->validated());
        StaffAttributeValidator::flushCache();

        $message = $result['restored'] ? 'Staff status restored.' : 'Staff status created.';

        return redirect()->route('staff-options.index')->with('success', $message);
    }

    public function edit(StaffStatus $staff_status): View
    {
        return view('staff_statuses.form', ['status' => $staff_status]);
    }

    public function update(StaffStatusRequest $request, StaffStatus $staff_status): RedirectResponse
    {
        $staff_status->update($request->validated());
        StaffAttributeValidator::flushCache();

        return redirect()->route('staff-options.index')->with('success', 'Staff status updated.');
    }

    public function destroy(StaffStatus $staff_status): RedirectResponse
    {
        $staff_status->delete();
        StaffAttributeValidator::flushCache();

        return redirect()->route('staff-options.index')->with('success', 'Staff status deleted.');
    }
}
