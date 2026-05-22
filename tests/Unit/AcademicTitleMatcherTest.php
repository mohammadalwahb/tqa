<?php

use App\Services\Evaluations\AcademicTitleMatcher;
use App\Services\Evaluations\MetricGradeResolver;
use App\Models\EvaluationScoreMetric;
use App\Models\EvaluationScoreMetricGrade;
use App\Models\StaffMember;

it('matches assistant professor title variants', function () {
    expect(AcademicTitleMatcher::matches('Assistant Prof.', 'Assistant Professor'))->toBeTrue()
        ->and(AcademicTitleMatcher::matches('Assistant Professor', 'assistant prof'))->toBeTrue();
});

it('resolves lecturer grade B for sum value 30', function () {
    $metric = new EvaluationScoreMetric([
        'grade_by_academic_title' => true,
    ]);

    $metric->setRelation('grades', collect([
        new EvaluationScoreMetricGrade([
            'academic_title' => 'Lecturer',
            'label'          => 'B',
            'min_value'      => 24,
            'max_value'      => 34,
            'sort_order'     => 0,
        ]),
        new EvaluationScoreMetricGrade([
            'academic_title' => 'Lecturer',
            'label'          => 'A',
            'min_value'      => 34,
            'max_value'      => 54,
            'sort_order'     => 1,
        ]),
    ]));

    $staff = new StaffMember(['academic_title' => 'Lecturer']);

    $result = app(MetricGradeResolver::class)->resolve(30.0, $metric, $staff);

    expect($result)->not->toBeNull()
        ->and($result['label'])->toBe('B');
});
