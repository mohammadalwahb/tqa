<?php

namespace App\Http\Controllers;

use App\Exports\StaffEvaluationReportExport;
use App\Http\Requests\ReportCustomCsvRequest;
use App\Models\College;
use App\Models\EvaluationPeriod;
use App\Models\StaffMember;
use App\Models\User;
use App\Services\Pdf\PdfDocumentBuilder;
use App\Services\Reporting\EvaluationReportService;
use App\Services\Reporting\ReportColumnCatalog;
use App\Services\Reporting\StaffReportCsvExporter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly EvaluationReportService $reports,
        private readonly PdfDocumentBuilder $pdfBuilder,
        private readonly ReportColumnCatalog $columnCatalog,
        private readonly StaffReportCsvExporter $csvExporter,
    ) {
    }

    public function index(Request $request): View
    {
        $period     = $this->resolvePeriod($request);
        $collegeId  = $this->reportCollegeScope($request->user());
        $progress   = $period ? $this->reports->universityProgress($period, $collegeId) : null;
        $staffRows  = $period ? $this->reports->staffProgressSummary($period, $collegeId) : collect();
        $derivedMetricColumns = collect();
        $reportQuestionColumns = $period ? $this->reports->reportQuestionColumns($period) : collect();

        return view('reports.index', [
            'period'                => $period,
            'periods'               => EvaluationPeriod::orderByDesc('start_date')->get(),
            'progress'              => $progress,
            'staffRows'             => $staffRows,
            'derivedMetricColumns'  => $derivedMetricColumns,
            'reportQuestionColumns' => $reportQuestionColumns,
            'scopedCollege'         => $collegeId ? College::find($collegeId) : null,
            'csvColumns'            => $period && $request->user()->isSuperAdmin()
                ? $this->columnCatalog->availableColumns($period)
                : [],
        ]);
    }

    public function staff(Request $request): View
    {
        return $this->index($request);
    }

    public function university(Request $request): View
    {
        return $this->index($request);
    }

    public function staffDetails(Request $request, StaffMember $staff): View
    {
        $this->authorizeStaffReportAccess($request->user(), $staff);

        $period = $this->resolvePeriod($request);
        $analytics = $period
            ? $this->reports->staffAnalytics($staff, $period)
            : ['overall' => null, 'by_category' => [], 'by_question' => [], 'extractions' => []];

        return view('reports.staff_details', [
            'staff'     => $staff->load(['department.college']),
            'period'    => $period,
            'periods'   => EvaluationPeriod::orderByDesc('start_date')->get(),
            'analytics' => $analytics,
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $period = $this->resolvePeriod($request);
        if (! $period) {
            return response('No period found', 404);
        }

        $collegeId = $this->reportCollegeScope($request->user());
        $progress  = $this->reports->universityProgress($period, $collegeId);
        $staffRows = $this->reports->staffProgress($period, $collegeId);
        $derivedMetricColumns = $this->reports->reportDerivedMetricColumns($period);
        $reportQuestionColumns = $this->reports->reportQuestionColumns($period);
        $scopedCollege = $collegeId ? College::find($collegeId) : null;

        return $this->pdfBuilder->download(
            'reports.pdf',
            compact('period', 'progress', 'staffRows', 'derivedMetricColumns', 'reportQuestionColumns', 'scopedCollege'),
            "tqa-report-{$period->id}.pdf",
        );
    }

    public function exportStaffPdf(Request $request, StaffMember $staff): Response
    {
        $this->authorizeStaffReportAccess($request->user(), $staff);

        $period = $this->resolvePeriod($request);
        abort_unless($period, 404);

        $staff->load(['department.college']);
        $pdfData = $this->reports->staffEvaluatorPdfData($staff, $period);

        $filename = sprintf(
            'tqa-staff-%s-period-%d.pdf',
            str($staff->full_name_en)->slug(),
            $period->id,
        );

        return $this->pdfBuilder->download(
            'reports.staff_pdf',
            compact('staff', 'period', 'pdfData'),
            $filename,
        );
    }

    public function exportCustomCsv(ReportCustomCsvRequest $request): StreamedResponse
    {
        $period = EvaluationPeriod::findOrFail($request->integer('period_id'));
        $columns = $this->columnCatalog->resolveColumns($period, $request->input('columns', []));

        abort_if($columns === [], 422);

        set_time_limit(0);

        return $this->csvExporter->download($period, $columns, $this->reportCollegeScope($request->user()));
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $period = $this->resolvePeriod($request);
        abort_unless($period, 404);

        $collegeId = $this->reportCollegeScope($request->user());
        $staffRows = $this->reports->staffProgress($period, $collegeId);
        $derivedMetricColumns = $this->reports->reportDerivedMetricColumns($period);
        $reportQuestionColumns = $this->reports->reportQuestionColumns($period);

        return Excel::download(
            new StaffEvaluationReportExport($staffRows, $derivedMetricColumns, $reportQuestionColumns),
            "tqa-staff-report-{$period->id}.xlsx"
        );
    }

    private function resolvePeriod(Request $request): ?EvaluationPeriod
    {
        if ($request->filled('period_id')) {
            return EvaluationPeriod::find($request->period_id);
        }

        return EvaluationPeriod::currentlyOpen() ?? EvaluationPeriod::orderByDesc('start_date')->first();
    }

    /**
     * Non–super-admin users with a college see only their college in reports.
     */
    private function reportCollegeScope(User $user): ?int
    {
        if ($user->isSuperAdmin()) {
            return null;
        }

        return $user->college_id ? (int) $user->college_id : null;
    }

    private function authorizeStaffReportAccess(User $user, StaffMember $staff): void
    {
        $collegeId = $this->reportCollegeScope($user);

        if ($collegeId !== null && (int) $staff->college_id !== $collegeId) {
            abort(403);
        }
    }
}
