<?php

namespace App\Http\Controllers;

use App\Exports\StaffEvaluationReportExport;
use App\Models\EvaluationPeriod;
use App\Models\StaffMember;
use App\Services\Pdf\PdfDocumentBuilder;
use App\Services\Reporting\EvaluationReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly EvaluationReportService $reports,
        private readonly PdfDocumentBuilder $pdfBuilder,
    ) {
    }

    public function index(Request $request): View
    {
        $period = $this->resolvePeriod($request);
        $progress = $period ? $this->reports->universityProgress($period) : null;
        $staffRows = $period ? $this->reports->staffProgressSummary($period) : collect();
        $derivedMetricColumns = collect();
        $reportQuestionColumns = $period ? $this->reports->reportQuestionColumns($period) : collect();

        return view('reports.index', [
            'period'                => $period,
            'periods'               => EvaluationPeriod::orderByDesc('start_date')->get(),
            'progress'              => $progress,
            'staffRows'             => $staffRows,
            'derivedMetricColumns'  => $derivedMetricColumns,
            'reportQuestionColumns' => $reportQuestionColumns,
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

        $progress  = $this->reports->universityProgress($period);
        $staffRows = $this->reports->staffProgress($period);
        $derivedMetricColumns = $this->reports->reportDerivedMetricColumns($period);
        $reportQuestionColumns = $this->reports->reportQuestionColumns($period);

        return $this->pdfBuilder->download(
            'reports.pdf',
            compact('period', 'progress', 'staffRows', 'derivedMetricColumns', 'reportQuestionColumns'),
            "tqa-report-{$period->id}.pdf",
        );
    }

    public function exportStaffPdf(Request $request, StaffMember $staff): Response
    {
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

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $period = $this->resolvePeriod($request);
        abort_unless($period, 404);

        $staffRows = $this->reports->staffProgress($period);
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
}
