<?php

namespace App\Http\Controllers;

use App\Models\CertificateTemplate;
use App\Models\EvaluationPeriod;
use App\Services\Certificates\CertificateRenderService;
use App\Services\Pdf\PdfDocumentBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateController extends Controller
{
    public function __construct(
        private readonly CertificateRenderService $renderer,
        private readonly PdfDocumentBuilder $pdf,
    ) {
    }

    public function index(Request $request): View
    {
        $staff = $request->user()->staffMember;
        abort_unless($staff, 403);

        $certificates = $this->renderer->publishedTemplatesForStaff($staff);

        return view('certificates.index', compact('certificates', 'staff'));
    }

    public function show(Request $request, EvaluationPeriod $period): View
    {
        $staff = $request->user()->staffMember;
        abort_unless($staff, 403);

        $template = CertificateTemplate::query()
            ->where('evaluation_period_id', $period->id)
            ->firstOrFail();

        abort_unless($this->renderer->staffMayView($template, $staff), 403);

        $data = $this->renderer->buildViewData($template, $staff);

        return view('certificates.render', array_merge($data, [
            'preview' => false,
        ]));
    }

    public function downloadPdf(Request $request, EvaluationPeriod $period): Response
    {
        $staff = $request->user()->staffMember;
        abort_unless($staff, 403);

        $template = CertificateTemplate::query()
            ->where('evaluation_period_id', $period->id)
            ->firstOrFail();

        abort_unless($this->renderer->staffMayView($template, $staff), 403);

        $data = $this->renderer->buildViewData($template, $staff);
        $filename = sprintf(
            'my-certificate-%s.pdf',
            str($period->name)->slug(),
        );

        return $this->pdf->download('certificates.pdf', $data, $filename);
    }

    public function background(Request $request, EvaluationPeriod $period): StreamedResponse
    {
        $template = CertificateTemplate::query()
            ->where('evaluation_period_id', $period->id)
            ->firstOrFail();

        abort_unless($template->background_path, 404);

        if ($request->user()->can('certificates.manage')) {
            return Storage::disk('local')->response($template->background_path);
        }

        $staff = $request->user()->staffMember;
        abort_unless($staff && $this->renderer->staffMayView($template, $staff), 403);

        return Storage::disk('local')->response($template->background_path);
    }
}
