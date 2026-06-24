<?php

use App\Support\NumberDisplay;

it('formats whole numbers without decimal places', function () {
    expect(NumberDisplay::formatScore(4.0))->toBe('4')
        ->and(NumberDisplay::formatScore(4.00))->toBe('4')
        ->and(NumberDisplay::formatScore(10.0))->toBe('10');
});

it('keeps non-zero decimal places', function () {
    expect(NumberDisplay::formatScore(4.5))->toBe('4.50')
        ->and(NumberDisplay::formatScore(4.25))->toBe('4.25')
        ->and(NumberDisplay::formatScore(3.33))->toBe('3.33');
});
