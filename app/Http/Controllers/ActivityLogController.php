<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        $activities = Activity::with('causer')->latest()->paginate(40);
        return view('activity_log.index', compact('activities'));
    }
}
