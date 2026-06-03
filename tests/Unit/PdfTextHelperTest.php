<?php

use App\Support\PdfTextHelper;

it('shapes arabic script for pdf output', function () {
    $input = 'بسم الله';
    $output = PdfTextHelper::present($input);

    expect(PdfTextHelper::containsArabicScript($input))->toBeTrue()
        ->and($output)->not->toBe($input)
        ->and(html_entity_decode($output))->not->toBe('');
});

it('leaves latin text unchanged aside from escaping', function () {
    $input = 'John Smith';
    expect(PdfTextHelper::present($input))->toBe('John Smith');
});
