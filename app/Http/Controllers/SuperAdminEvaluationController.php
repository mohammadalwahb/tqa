<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuperAdminStaffZipRequest;
use App\Models\EvaluationPeriod;
use App\Services\Evaluations\SuperAdminEvaluationAssignmentService;
use App\Services\Reporting\StaffReportZipExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SuperAdminEvaluationController extends Controller
{
    public function __construct(
        private readonly SuperAdminEvaluationAssignmentService $assignments,
        private readonly StaffReportZipExporter $zipExporter,
    ) {
    }

    public function index(Request $request): View
    {
        $period = $this->resolvePeriod($request);
        $evaluations = $this->assignments->sharedEvaluationsForPeriod($period);

        return view('super-admin.evaluations.index', [
            'period'       => $period,
            'periods'      => EvaluationPeriod::orderByDesc('start_date')->get(),
            'evaluations'  => $evaluations,
            'zipStaffRows' => $period ? $this->zipExporter->eligibleStaffRows($period) : collect(),
        ]);
    }

    public function exportStaffPdfsZip(SuperAdminStaffZipRequest $request): BinaryFileResponse|RedirectResponse
    {
        $period = EvaluationPeriod::findOrFail($request->validated('period_id'));

        set_time_limit(0);

        try {
            $zipPath = $this->zipExporter->createZipForPeriod(
                $period,
                submittedOnly: true,
                staffIds: $request->validated('staff_ids'),
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return response()
            ->download($zipPath, "tqa-staff-reports-period-{$period->id}.zip")
            ->deleteFileAfterSend(true);
    }

    private function resolvePeriod(Request $request): ?EvaluationPeriod
    {
        if ($request->filled('period_id')) {
            return EvaluationPeriod::find($request->period_id);
        }

        return EvaluationPeriod::currentlyOpen() ?? EvaluationPeriod::orderByDesc('start_date')->first();
    }
}
