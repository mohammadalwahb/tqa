<?php

use App\Models\EvaluationScoreMetric;
use App\Models\EvaluationScoreMetricGrade;
use App\Models\StaffMember;
use App\Services\Evaluations\MetricGradeResolver;

it('resolves grade from stored academic title bands on the metric', function () {
    $metric = new EvaluationScoreMetric([
        'id'                      => 1,
        'grade_by_academic_title' => true,
    ]);

    $grade = new EvaluationScoreMetricGrade([
        'academic_title' => 'Lecturer',
        'label'          => 'A',
        'min_value'      => 35,
        'max_value'      => 54,
        'sort_order'     => 0,
    ]);

    $metric->setRelation('grades', collect([$grade]));
    $staff = new StaffMember(['academic_title' => 'Lecturer']);

    $result = app(MetricGradeResolver::class)->resolve(40.0, $metric, $staff);

    expect($result)->not->toBeNull()
        ->and($result['label'])->toBe('A');
});

it('returns null when staff title has no configured bands', function () {
    $metric = new EvaluationScoreMetric([
        'id'                      => 1,
        'grade_by_academic_title' => true,
    ]);

    $grade = new EvaluationScoreMetricGrade([
        'academic_title' => 'Lecturer',
        'label'          => 'A',
        'min_value'      => 35,
        'max_value'      => 54,
        'sort_order'     => 0,
    ]);

    $metric->setRelation('grades', collect([$grade]));
    $staff = new StaffMember(['academic_title' => 'Professor']);

    $result = app(MetricGradeResolver::class)->resolve(40.0, $metric, $staff);

    expect($result)->toBeNull();
});
