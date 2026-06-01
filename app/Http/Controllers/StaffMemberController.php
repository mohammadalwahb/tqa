<?php



namespace App\Http\Controllers;



use App\Enums\StaffLookupField;

use App\Http\Requests\StaffMemberRequest;

use App\Http\Requests\PurgeAllStaffRequest;

use App\Imports\StaffImportTemplate;

use App\Imports\StaffMemberImport;

use App\Models\College;

use App\Models\Department;

use App\Models\StaffLookupOption;

use App\Models\StaffMember;

use App\Models\StaffStatus;

use App\Services\Committees\CommitteeEvaluationSyncService;

use App\Services\Staff\StaffOrganizationalRoleAssigner;

use App\Services\Staff\StaffPurgeService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Response;

use Illuminate\View\View;

use Maatwebsite\Excel\Facades\Excel;

use Symfony\Component\HttpFoundation\StreamedResponse;



class StaffMemberController extends Controller

{

    public function index(Request $request): View

    {

        $this->authorize('viewAny', StaffMember::class);



        $query = StaffMember::query()

            ->with(['college', 'department', 'status'])

            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {

                $q->where('full_name_en', 'like', "%{$s}%")

                  ->orWhere('email', 'like', "%{$s}%");

            }));



        $this->applyDepartmentScope($query, $request);



        $staff = $query->latest()->paginate(25)->withQueryString();



        return view('staff.index', [

            'staff'              => $staff,

            'colleges'           => $this->collegesForUser(),

            'departments'        => $this->departmentsForUser(),

            'departmentHeadMode' => $this->isDepartmentHeadOnly(),

            'headedDepartment'   => $request->user()?->headedDepartment(),

        ]);

    }



    public function create(): View

    {

        $this->authorize('create', StaffMember::class);



        return view('staff.form', $this->commonData(new StaffMember()));

    }



    public function store(

        StaffMemberRequest $request,

        StaffOrganizationalRoleAssigner $orgRoles,

        CommitteeEvaluationSyncService $committeeSync,

    ): RedirectResponse {

        $this->authorize('create', StaffMember::class);

        $staff = StaffMember::create($request->validated());

        $this->syncOrganizationalRoles($orgRoles, $staff);

        app(\App\Services\Committees\CommitteeService::class)->ensureUserForStaff($staff->fresh());

        $syncResult = $committeeSync->reconcileLocalEvaluationsForStaff($staff->fresh());

        $message = __('messages.staff_created');

        if ($syncResult['created'] > 0) {
            $message .= ' '.__('messages.staff_evaluations_added', ['count' => $syncResult['created']]);
        }

        return redirect()->route('staff.index')->with('success', $message);

    }



    public function show(StaffMember $staff): View

    {

        $this->authorize('view', $staff);



        $staff->load(['college', 'department', 'status', 'user']);



        return view('staff.show', compact('staff'));

    }



    public function edit(StaffMember $staff): View

    {

        $this->authorize('update', $staff);



        return view('staff.form', $this->commonData($staff));

    }



    public function update(

        StaffMemberRequest $request,

        StaffMember $staff,

        StaffOrganizationalRoleAssigner $orgRoles,

        CommitteeEvaluationSyncService $committeeSync,

    ): RedirectResponse {

        $this->authorize('update', $staff);

        $previousDepartmentId = (int) $staff->department_id;

        $staff->update($request->validated());

        $this->syncOrganizationalRoles($orgRoles, $staff->fresh());

        app(\App\Services\Committees\CommitteeService::class)->ensureUserForStaff($staff->fresh());

        $syncResult = $committeeSync->reconcileLocalEvaluationsForStaff($staff->fresh(), $previousDepartmentId);

        $message = __('messages.staff_updated');

        if ($syncResult['created'] > 0) {
            $message .= ' '.__('messages.staff_evaluations_added', ['count' => $syncResult['created']]);
        }
        if ($syncResult['removed'] > 0) {
            $message .= ' '.__('messages.staff_evaluations_removed', ['count' => $syncResult['removed']]);
        }

        return redirect()->route('staff.index')->with('success', $message);

    }



    public function destroy(StaffMember $staff): RedirectResponse

    {

        $this->authorize('delete', $staff);

        $removed = app(CommitteeEvaluationSyncService::class)->removeAllEvaluationsForEvaluatee($staff);

        $staff->delete();

        $message = __('messages.staff_deleted');
        if ($removed > 0) {
            $message .= ' '.__('messages.staff_evaluations_removed', ['count' => $removed]);
        }

        return redirect()->route('staff.index')->with('success', $message);

    }



    public function template(): StreamedResponse

    {

        $this->authorize('import', StaffMember::class);



        $headers = StaffImportTemplate::HEADERS;

        $rows    = StaffImportTemplate::rows();



        return Response::stream(function () use ($headers, $rows) {

            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, $headers);

            foreach ($rows as $r) {

                fputcsv($out, $r);

            }

            fclose($out);

        }, 200, [

            'Content-Type'        => 'text/csv; charset=UTF-8',

            'Content-Disposition' => 'attachment; filename="staff_import_template.csv"',

        ]);

    }



    public function purgeAll(PurgeAllStaffRequest $request, StaffPurgeService $purge): RedirectResponse

    {

        $count = $purge->purgeAllPermanently();



        return redirect()

            ->route('staff.index')

            ->with('success', __('messages.staff_purged', ['count' => $count]));

    }



    public function import(Request $request): RedirectResponse

    {

        $this->authorize('import', StaffMember::class);



        $request->validate([

            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],

        ]);



        $import = app(StaffMemberImport::class);

        Excel::import($import, $request->file('file'));



        $issuesSuffix = empty($import->errors)
            ? ''
            : __('messages.staff_import_issues', ['issues' => implode(' | ', array_slice($import->errors, 0, 10))]);

        return redirect()->route('staff.index')->with(
            empty($import->errors) ? 'success' : 'error',
            __('messages.staff_import', [
                'created' => $import->created,
                'updated' => $import->updated,
                'issues' => $issuesSuffix,
            ])
        );

    }



    private function syncOrganizationalRoles(StaffOrganizationalRoleAssigner $orgRoles, StaffMember $staff): void

    {

        $staff->load(['college', 'department']);

        if ($staff->college && $staff->department) {

            $orgRoles->assignFromPosition($staff, $staff->position, $staff->college, $staff->department);

        }

    }



    private function commonData(StaffMember $staff): array

    {

        $headedDepartment = auth()->user()?->headedDepartment();



        if ($headedDepartment && ! $staff->exists) {

            $staff->college_id    = $headedDepartment->college_id;

            $staff->department_id = $headedDepartment->id;

        }



        $lookupOptions = [];

        foreach (StaffLookupField::casesList() as $field) {

            $lookupOptions[$field->value] = StaffLookupOption::query()

                ->forField($field)

                ->active()

                ->orderBy('name')

                ->pluck('name');

        }



        return [

            'staff'              => $staff,

            'colleges'           => $this->collegesForUser(),

            'departments'        => $this->departmentsForUser(),

            'statuses'           => StaffStatus::query()->active()->orderBy('name')->get(),

            'lookupOptions'      => $lookupOptions,

            'departmentHeadMode' => $this->isDepartmentHeadOnly(),

            'headedDepartment'   => $headedDepartment,

        ];

    }



    private function applyDepartmentScope($query, Request $request): void

    {

        if ($department = $request->user()?->headedDepartment()) {

            $query->where('department_id', $department->id);



            return;

        }



        $query->when($request->college_id, fn ($q, $id) => $q->where('college_id', $id))

            ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id));

    }



    private function isDepartmentHeadOnly(): bool

    {

        $user = auth()->user();



        return $user

            && $user->can('staff.manage_department')

            && ! $user->can('staff.manage');

    }



    private function collegesForUser()

    {

        if ($department = auth()->user()?->headedDepartment()) {

            return College::where('id', $department->college_id)->orderBy('name_en')->get();

        }



        return College::orderBy('name_en')->get();

    }



    private function departmentsForUser()

    {

        if ($department = auth()->user()?->headedDepartment()) {

            return Department::with('college')->where('id', $department->id)->orderBy('name_en')->get();

        }



        return Department::with('college')->orderBy('name_en')->get();

    }

}

