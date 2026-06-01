<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with(['roles', 'college', 'staffMember'])->orderBy('name')->get();
        return view('users.index', compact('users'));
    }

    public function edit(User $user): View
    {
        return view('users.form', [
            'user'     => $user,
            'roles'    => Role::orderBy('name')->get(),
            'colleges' => College::orderBy('name_en')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'college_id' => ['nullable', 'exists:colleges,id'],
            'is_active'  => ['sometimes', 'boolean'],
            'roles'      => ['array'],
            'roles.*'    => ['string', 'exists:roles,name'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $user->update([
            'name'       => $data['name'],
            'college_id' => $data['college_id'] ?? null,
            'is_active'  => $data['is_active'],
        ]);

        $user->syncRoles($data['roles'] ?? []);

        return redirect()->route('users.index')->with('success', __('messages.user_updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', __('messages.user_cannot_delete_self'));
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', __('messages.user_deleted'));
    }
}
