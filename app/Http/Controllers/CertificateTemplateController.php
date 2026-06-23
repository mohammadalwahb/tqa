<?php

namespace App\Http\Controllers;

use App\Http\Requests\CertificateTemplateRequest;
use App\Models\CertificateTemplate;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationForm;
use App\Models\StaffMember;
use App\Services\Certificates\CertificateFieldCatalog;
use App\Services\Certificates\CertificateRenderService;
use App\Services\Certificates\CertificateTemplateService;
use App\Services\Pdf\PdfDocumentBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateTemplateController extends Controller
{
    public function __construct(
        private readonly CertificateTemplateService $templates,
        private readonly CertificateFieldCatalog $fields,
        private readonly CertificateRenderService $renderer,
        private readonly PdfDocumentBuilder $pdf,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', CertificateTemplate::class);
        $periods = EvaluationPeriod::orderByDesc('start_date')->get();
        $templates = CertificateTemplate::with(['period', 'form'])->get()->keyBy('evaluation_period_id');

        return view('certificates.templates.index', compact('periods', 'templates'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', CertificateTemplate::class);
        $period = EvaluationPeriod::findOrFail($request->integer('period_id'));
        $template = $this->templates->findOrNewForPeriod($period);

        if ($request->filled('form_id')) {
            $form = EvaluationForm::findOrFail($request->integer('form_id'));
        } else {
            $form = $template->evaluation_form_id
                ? EvaluationForm::find($template->evaluation_form_id)
                : $this->templates->defaultFormForPeriod($period);
        }

        abort_unless($form, 404);

        return view('certificates.templates.edit', [
            'template'      => $template,
            'period'        => $period,
            'form'          => $form,
            'forms'         => EvaluationForm::where('target_type', 'staff')->orderBy('name')->get(),
            'fieldCatalog'  => $this->fields->availableFields($form, $period),
            'placedFields'  => $template->placedFields(),
        ]);
    }

    public function store(CertificateTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', CertificateTemplate::class);
        $template = new CertificateTemplate();
        $this->templates->save(
            $template,
            $request->validated(),
            $request->user(),
            $request->file('background_image'),
        );

        return redirect()
            ->route('certificate-templates.edit', $template)
            ->with('success', __('certificates.template_saved'));
    }

    public function edit(CertificateTemplate $certificateTemplate): View
    {
        $this->authorize('update', $certificateTemplate);
        $certificateTemplate->load(['period', 'form']);

        return view('certificates.templates.edit', [
            'template'     => $certificateTemplate,
            'period'       => $certificateTemplate->period,
            'form'         => $certificateTemplate->form,
            'forms'        => EvaluationForm::where('target_type', 'staff')->orderBy('name')->get(),
            'fieldCatalog' => $this->fields->availableFields($certificateTemplate->form, $certificateTemplate->period),
            'placedFields' => $certificateTemplate->placedFields(),
        ]);
    }

    public function update(CertificateTemplateRequest $request, CertificateTemplate $certificateTemplate): RedirectResponse
    {
        $this->authorize('update', $certificateTemplate);
        $this->templates->save(
            $certificateTemplate,
            $request->validated(),
            $request->user(),
            $request->file('background_image'),
        );

        return redirect()
            ->route('certificate-templates.edit', $certificateTemplate)
            ->with('success', __('certificates.template_saved'));
    }

    public function destroy(CertificateTemplate $certificateTemplate): RedirectResponse
    {
        $this->authorize('delete', $certificateTemplate);
        $this->templates->deleteBackground($certificateTemplate);
        $certificateTemplate->delete();

        return redirect()
            ->route('certificate-templates.index')
            ->with('success', __('certificates.template_deleted'));
    }

    public function togglePublished(CertificateTemplate $certificateTemplate): RedirectResponse
    {
        $this->authorize('update', $certificateTemplate);

        $this->templates->togglePublished($certificateTemplate, ! $certificateTemplate->is_published);

        return back()->with('success', __('certificates.publish_toggled'));
    }

    public function background(CertificateTemplate $certificateTemplate): StreamedResponse
    {
        $this->authorize('view', $certificateTemplate);

        abort_unless($certificateTemplate->background_path, 404);

        return Storage::disk('local')->response($certificateTemplate->background_path);
    }

    public function preview(Request $request, CertificateTemplate $certificateTemplate, StaffMember $staff): View
    {
        $this->authorize('view', $certificateTemplate);

        $data = $this->renderer->buildViewData($certificateTemplate, $staff);

        return view('certificates.render', array_merge($data, [
            'preview' => true,
        ]));
    }

    public function exportPdf(CertificateTemplate $certificateTemplate, StaffMember $staff): Response
    {
        $this->authorize('view', $certificateTemplate);

        $data = $this->renderer->buildViewData($certificateTemplate, $staff);
        $filename = sprintf(
            'certificate-%s-period-%d.pdf',
            str($staff->full_name_en)->slug(),
            $certificateTemplate->evaluation_period_id,
        );

        return $this->pdf->download('certificates.pdf', $data, $filename);
    }

    public function staffPicker(CertificateTemplate $certificateTemplate): View
    {
        $this->authorize('view', $certificateTemplate);

        $staffRows = app(\App\Services\Reporting\EvaluationReportService::class)
            ->staffProgressSummary($certificateTemplate->period)
            ->filter(fn (array $row) => (int) $row['completed'] > 0);

        return view('certificates.templates.staff-picker', [
            'template'  => $certificateTemplate,
            'staffRows' => $staffRows,
        ]);
    }
}
