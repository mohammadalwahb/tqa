<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Send the authenticated user to a landing page that matches their role.
     */
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return redirect()->route('dashboard');
        }

        if ($user->can('committees.manage')) {
            return redirect()->route('committees.index');
        }

        if ($user->can('evaluations.submit')) {
            return redirect()->route('evaluations.index');
        }

        if ($user->headedDepartment()) {
            return redirect()->route('staff.index');
        }

        if ($user->can('reports.view')) {
            return redirect()->route('reports.index');
        }

        if ($user->can('certificates.view_own')) {
            return redirect()->route('certificates.index');
        }

        // Authenticated user without permissions — sign them out.
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => __('login.no_role'),
        ]);
    }
}
