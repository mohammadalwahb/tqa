<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Committee;
use App\Models\Department;
use App\Models\EvaluationForm;
use App\Models\EvaluationPeriod;
use App\Services\Committees\CommitteeService;
use App\Services\Committees\CommitteeStaffOptionsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CommitteeController extends Controller
{
    public function __construct(
        private readonly CommitteeService $committees,
        private readonly CommitteeStaffOptionsService $staffOptions,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Committee::with(['college', 'department', 'period', 'members.staffMember', 'members.user'])
            ->withCount('evaluations')
            ->latest();

        if (! $user->can('committees.manage')) {
            $query->where('created_by', $user->id);
        } elseif (! $user->isSuperAdmin() && $user->college_id) {
            $query->where('college_id', $user->college_id);
        }

        $committees = $query->get();

        return view('committees.index', compact('committees'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        $college = $user->college;
        if ($user->isSuperAdmin() && ! $college && $request->filled('college_id')) {
            $college = College::find($request->college_id);
        }

        $period = EvaluationPeriod::currentlyOpen()
            ?? EvaluationPeriod::orderByDesc('start_date')->first();

        $departments = $college
            ? Department::where('college_id', $college->id)->orderBy('name_en')->get()
            : collect();

        return view('committees.create', [
            'user'        => $user,
            'college'     => $college,
            'colleges'    => College::orderBy('name_en')->get(),
            'departments' => $departments,
            'period'      => $period,
            'periods'     => EvaluationPeriod::orderByDesc('start_date')->get(),
            'forms'       => EvaluationForm::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('type');
        if (! in_array($type, ['local', 'hd'], true)) {
            return back()->with('error', __('messages.committee_unknown_type'));
        }

        $request->validate([
            'department_id'        => ['required', 'exists:departments,id'],
            'evaluation_period_id' => ['required', 'exists:evaluation_periods,id'],
            'evaluation_form_id'   => ['nullable', 'exists:evaluation_forms,id'],
            'name'                 => ['nullable', 'string', 'max:160'],
        ]);

        if ($type === 'local') {
            $request->validate([
                'same_department_member_id'    => ['required', 'integer', 'exists:staff_members,id'],
                'other_department_member_id'   => ['required', 'integer', 'exists:staff_members,id', 'different:same_department_member_id'],
            ]);
        } else {
            $request->validate([
                'same_department_member_id'  => ['required', 'integer', 'exists:staff_members,id'],
                'other_department_member_id' => ['required', 'integer', 'exists:staff_members,id', 'different:same_department_member_id'],
            ]);
        }

        try {
            if ($type === 'local') {
                $committee = $this->committees->createLocalCommittee($request->user(), $request->only(
                    'department_id', 'same_department_member_id', 'other_department_member_id',
                    'evaluation_period_id', 'evaluation_form_id', 'name'
                ));
            } else {
                $committee = $this->committees->createHdCommittee($request->user(), $request->only(
                    'department_id', 'same_department_member_id', 'other_department_member_id',
                    'evaluation_period_id', 'evaluation_form_id', 'name'
                ));
            }
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('committees.show', $committee)
            ->with('success', __('messages.committee_created'));
    }

    public function show(Committee $committee): View
    {
        $committee->load([
            'college', 'department', 'period', 'form',
            'members.staffMember.department', 'members.user',
            'evaluations.evaluatee', 'evaluations.evaluator',
        ]);

        return view('committees.show', compact('committee'));
    }

    public function destroy(Committee $committee): RedirectResponse
    {
        $committee->evaluations()->delete();
        $committee->members()->delete();
        $committee->delete();

        return redirect()->route('committees.index')->with('success', __('messages.committee_deleted'));
    }

    public function staffOptions(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->staffOptions->optionsForRequest($request));
    }
}
