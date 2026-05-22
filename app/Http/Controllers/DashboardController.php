<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Committee;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\StaffMember;
use App\Models\User;
use App\Services\Reporting\EvaluationReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly EvaluationReportService $reports)
    {
    }

    public function index(Request $request): View
    {
        $user   = $request->user();
        $period = EvaluationPeriod::currentlyOpen();

        $stats = [
            'colleges'         => College::count(),
            'departments'      => Department::count(),
            'staff'            => StaffMember::where('is_active', true)->count(),
            'teaching_staff'   => StaffMember::where('is_active', true)->where('is_teaching_staff', true)->count(),
            'users'            => User::count(),
            'committees'       => Committee::when($period, fn ($q) => $q->where('evaluation_period_id', $period->id))->count(),
            'evaluations_open' => Evaluation::when($period, fn ($q) => $q->where('evaluation_period_id', $period->id))
                                            ->where('status', Evaluation::STATUS_DRAFT)->count(),
            'evaluations_done' => Evaluation::when($period, fn ($q) => $q->where('evaluation_period_id', $period->id))
                                            ->where('status', Evaluation::STATUS_SUBMITTED)->count(),
        ];

        $universityProgress = $period ? $this->reports->universityProgress($period) : null;

        $myPending = collect();
        if ($user && $period) {
            $myPending = Evaluation::where('evaluator_user_id', $user->id)
                ->where('evaluation_period_id', $period->id)
                ->where('status', Evaluation::STATUS_DRAFT)
                ->with(['evaluatee.department', 'committee'])
                ->latest()
                ->take(10)
                ->get();
        }

        return view('dashboard', [
            'stats'              => $stats,
            'period'             => $period,
            'universityProgress' => $universityProgress,
            'myPending'          => $myPending,
        ]);
    }
}
