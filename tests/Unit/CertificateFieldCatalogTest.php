<?php

use App\Models\EvaluationForm;
use App\Models\EvaluationScoreMetric;
use App\Services\Certificates\CertificateFieldCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
});

it('includes derived metrics in the certificate field catalog', function () {
    $form = EvaluationForm::where('target_type', 'staff')->firstOrFail();

    $metric = EvaluationScoreMetric::create([
        'evaluation_form_id' => $form->id,
        'name' => 'Overall Grade',
        'operation' => EvaluationScoreMetric::OPERATION_AVERAGE,
        'show_in_reports' => true,
        'sort_order' => 1,
    ]);

    $fields = app(CertificateFieldCatalog::class)->availableFields($form);
    $keys = collect($fields)->pluck('key');

    expect($keys)->toContain('metric:' . $metric->id);
});

it('resolves static text fields for certificate rendering', function () {
    $form = EvaluationForm::where('target_type', 'staff')->firstOrFail();
    $catalog = app(CertificateFieldCatalog::class);

    $resolved = $catalog->resolvePlacedFields($form, [[
        'key' => 'text:1',
        'content' => 'Certificate of Excellence',
        'x' => 10,
        'y' => 20,
        'width' => 400,
        'font_size' => 30,
        'font_weight' => 'bold',
        'color' => '#000000',
        'text_align' => 'center',
    ]]);

    expect($resolved)->toHaveCount(1)
        ->and($resolved[0]['content'])->toBe('Certificate of Excellence')
        ->and($resolved[0]['group'])->toBe('text');
});
