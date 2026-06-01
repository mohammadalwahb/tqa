<?php

namespace App\Http\Controllers;

use App\Http\Requests\CoordinatorRequest;
use App\Models\College;
use App\Models\User;
use App\Services\Users\CoordinatorUserProvisioner;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CoordinatorController extends Controller
{
    public function index(): View
    {
        $coordinators = User::role(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR)
            ->with('college')
            ->orderBy('name')
            ->get();

        return view('coordinators.index', compact('coordinators'));
    }

    public function create(): View
    {
        return view('coordinators.form', [
            'coordinator' => new User(),
            'colleges'    => College::orderBy('name_en')->get(),
        ]);
    }

    public function store(CoordinatorRequest $request, CoordinatorUserProvisioner $provisioner): RedirectResponse
    {
        $result = $provisioner->provision($request->validated());
        $user    = $result['user'];

        $user->syncRoles([RolePermissionSeeder::ROLE_QUALITY_COORDINATOR]);

        $message = $result['restored']
            ? __('messages.coordinator_restored')
            : __('messages.coordinator_created');

        return redirect()->route('coordinators.index')->with('success', $message);
    }

    public function edit(User $coordinator): View
    {
        return view('coordinators.form', [
            'coordinator' => $coordinator,
            'colleges'    => College::orderBy('name_en')->get(),
        ]);
    }

    public function update(CoordinatorRequest $request, User $coordinator): RedirectResponse
    {
        $coordinator->update($request->validated());
        if (! $coordinator->hasRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR)) {
            $coordinator->assignRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR);
        }

        return redirect()->route('coordinators.index')->with('success', __('messages.coordinator_updated'));
    }

    public function destroy(User $coordinator): RedirectResponse
    {
        $coordinator->removeRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR);
        $coordinator->college_id = null;
        $coordinator->save();

        return redirect()->route('coordinators.index')->with('success', __('messages.coordinator_removed'));
    }
}
