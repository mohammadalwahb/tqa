<?php

namespace App\Services\Certificates;

use App\Models\CertificateTemplate;
use App\Models\EvaluationForm;
use App\Models\EvaluationPeriod;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CertificateTemplateService
{
    public function __construct(
        private readonly CertificateFieldCatalog $fields,
    ) {
    }

    public function findOrNewForPeriod(EvaluationPeriod $period): CertificateTemplate
    {
        return CertificateTemplate::firstOrNew([
            'evaluation_period_id' => $period->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(
        CertificateTemplate $template,
        array $data,
        User $actor,
        ?UploadedFile $background = null,
    ): CertificateTemplate {
        $template->fill([
            'evaluation_period_id' => $data['evaluation_period_id'],
            'evaluation_form_id'   => $data['evaluation_form_id'],
            'layout'               => ['fields' => $data['layout_fields'] ?? []],
            'is_published'         => (bool) ($data['is_published'] ?? false),
            'canvas_width'         => CertificateTemplate::CANVAS_WIDTH,
            'canvas_height'        => CertificateTemplate::CANVAS_HEIGHT,
            'created_by'           => $template->exists ? $template->created_by : $actor->id,
        ]);

        if ($background) {
            $this->storeBackground($template, $background);
        }

        $template->save();

        return $template->fresh(['period', 'form']);
    }

    public function togglePublished(CertificateTemplate $template, bool $published): CertificateTemplate
    {
        $template->update(['is_published' => $published]);

        return $template->fresh();
    }

    public function deleteBackground(CertificateTemplate $template): void
    {
        if ($template->background_path) {
            Storage::disk('local')->delete($template->background_path);
            $template->update(['background_path' => null]);
        }
    }

    private function storeBackground(CertificateTemplate $template, UploadedFile $file): void
    {
        if ($template->background_path) {
            Storage::disk('local')->delete($template->background_path);
        }

        $path = $file->store('certificates/backgrounds', 'local');
        $template->background_path = $path;
    }

    public function defaultFormForPeriod(EvaluationPeriod $period): ?EvaluationForm
    {
        $formId = \App\Models\Committee::query()
            ->where('evaluation_period_id', $period->id)
            ->whereNotNull('evaluation_form_id')
            ->value('evaluation_form_id');

        if ($formId) {
            return EvaluationForm::find($formId);
        }

        return EvaluationForm::query()
            ->where('target_type', 'staff')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }
}
