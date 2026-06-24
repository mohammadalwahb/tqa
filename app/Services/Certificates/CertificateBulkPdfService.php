<?php

namespace App\Services\Certificates;

use App\Models\CertificateTemplate;
use App\Models\StaffMember;
use App\Services\Reporting\EvaluationReportService;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CertificateBulkPdfService
{
    public function __construct(
        private readonly CertificateRenderService $renderer,
        private readonly EvaluationReportService $reports,
    ) {
    }

    /**
     * @return Collection<int, int>
     */
    public function eligibleStaffIds(CertificateTemplate $template): Collection
    {
        $template->loadMissing('period');

        return $this->reports
            ->staffProgressSummary($template->period)
            ->filter(fn (array $row) => (int) $row['completed'] > 0)
            ->pluck('staff.id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * @param  list<int>  $staffIds
     * @return array<string, mixed>
     */
    public function buildBulkViewData(CertificateTemplate $template, array $staffIds): array
    {
        $template->loadMissing(['period', 'form']);

        $staffMembers = StaffMember::query()
            ->whereIn('id', $staffIds)
            ->orderBy('full_name_en')
            ->get();

        $width = $template->canvas_width ?: CertificateTemplate::CANVAS_WIDTH;
        $height = $template->canvas_height ?: CertificateTemplate::CANVAS_HEIGHT;

        $pages = [];
        foreach ($staffMembers as $staff) {
            if (! $this->renderer->staffHasCertificateData($staff, $template)) {
                continue;
            }

            $pages[] = [
                'fields' => $this->renderer->renderFields($template, $staff),
            ];
        }

        if ($pages === []) {
            throw new InvalidArgumentException(__('certificates.bulk_no_staff'));
        }

        return [
            'template' => $template,
            'width'    => $width,
            'height'   => $height,
            'pages'    => $pages,
        ];
    }
}
