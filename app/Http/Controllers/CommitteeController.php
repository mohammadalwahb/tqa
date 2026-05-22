<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Committee;
use App\Models\Department;
use App\Models\EvaluationForm;
use App\Models\EvaluationPeriod;
use App\Models\StaffMember;
use App\Services\Committees\CommitteeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CommitteeController extends Controller
{
    public function __construct(private readonly CommitteeService $committees)
    {
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
            return back()->with('error', 'Unknown committee type.');
        }

        $request->validate([
            'department_id'        => ['required', 'exists:departments,id'],
            'evaluation_period_id' => ['required', 'exists:evaluation_periods,id'],
            'evaluation_form_id'   => ['nullable', 'exists:evaluation_forms,id'],
            'name'                 => ['nullable', 'string', 'max:160'],
        ]);

        if ($type === 'local') {
            $request->validate([
                'same_department_member_ids'   => ['required', 'array', 'size:2'],
                'same_department_member_ids.*' => ['integer', 'exists:staff_members,id'],
                'other_department_member_id'   => ['required', 'integer', 'exists:staff_members,id'],
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
                    'department_id', 'same_department_member_ids', 'other_department_member_id',
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
            ->with('success', 'Committee created successfully.');
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

        return redirect()->route('committees.index')->with('success', 'Committee deleted.');
    }

    public function staffOptions(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'college_id'            => ['required', 'exists:colleges,id'],
            'department_id'         => ['nullable', 'exists:departments,id'],
            'exclude_department_id' => ['nullable', 'exists:departments,id'],
            'exclude_head'          => ['nullable'],
            'filter'                => ['nullable', 'in:department,college'],
        ]);

        $query = StaffMember::query()
            ->where('is_active', true)
            ->where('college_id', $request->college_id)
            ->orderBy('full_name_en');

        if ($request->input('filter') === 'department' && $request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        } elseif ($request->filled('department_id') && $request->input('filter') !== 'college') {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('exclude_department_id')) {
            $query->where('department_id', '<>', $request->exclude_department_id);
        }

        if ($request->boolean('exclude_head') && $request->filled('department_id')) {
            $headId = Department::whereKey($request->department_id)->value('head_staff_id');
            if ($headId) {
                $query->where('id', '<>', $headId);
            }
        }

        $rows = $query->with('department')->get();

        return response()->json($rows->map(fn ($s) => [
            'id'    => $s->id,
            'label' => $s->full_name_en . ' · ' . ($s->department?->name_en ?? ''),
        ]));
    }
}
