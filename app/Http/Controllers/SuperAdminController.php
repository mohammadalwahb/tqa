<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuperAdminRequest;
use App\Models\User;
use App\Services\Users\CoordinatorUserProvisioner;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function index(): View
    {
        $superAdmins = User::role(RolePermissionSeeder::ROLE_SUPER_ADMIN)
            ->orderBy('name')
            ->get();

        return view('super-admins.index', compact('superAdmins'));
    }

    public function create(): View
    {
        return view('super-admins.form', [
            'superAdmin' => new User(),
        ]);
    }

    public function store(SuperAdminRequest $request, CoordinatorUserProvisioner $provisioner): RedirectResponse
    {
        $validated = array_merge($request->validated(), ['college_id' => null]);

        $result = $provisioner->provision($validated);
        $user   = $result['user'];

        $user->syncRoles([RolePermissionSeeder::ROLE_SUPER_ADMIN]);

        $message = $result['restored']
            ? __('messages.super_admin_restored')
            : __('messages.super_admin_created');

        return redirect()->route('super-admins.index')->with('success', $message);
    }

    public function edit(User $super_admin): View
    {
        $this->ensureSuperAdmin($super_admin);

        return view('super-admins.form', [
            'superAdmin' => $super_admin,
        ]);
    }

    public function update(SuperAdminRequest $request, User $super_admin): RedirectResponse
    {
        $this->ensureSuperAdmin($super_admin);

        $super_admin->update($request->only(['name', 'is_active']));

        if (! $super_admin->hasRole(RolePermissionSeeder::ROLE_SUPER_ADMIN)) {
            $super_admin->assignRole(RolePermissionSeeder::ROLE_SUPER_ADMIN);
        }

        return redirect()->route('super-admins.index')->with('success', __('messages.super_admin_updated'));
    }

    public function destroy(User $super_admin): RedirectResponse
    {
        $this->ensureSuperAdmin($super_admin);

        if ((int) $super_admin->id === (int) auth()->id()) {
            return redirect()->route('super-admins.index')
                ->with('error', __('messages.super_admin_cannot_remove_self'));
        }

        if (User::role(RolePermissionSeeder::ROLE_SUPER_ADMIN)->count() <= 1) {
            return redirect()->route('super-admins.index')
                ->with('error', __('messages.super_admin_min_one'));
        }

        $super_admin->removeRole(RolePermissionSeeder::ROLE_SUPER_ADMIN);

        return redirect()->route('super-admins.index')->with('success', __('messages.super_admin_removed'));
    }

    private function ensureSuperAdmin(User $user): void
    {
        abort_unless($user->hasRole(RolePermissionSeeder::ROLE_SUPER_ADMIN), 404);
    }
}
