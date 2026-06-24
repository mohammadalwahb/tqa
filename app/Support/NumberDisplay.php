<?php

namespace App\Support;

class NumberDisplay
{
    public static function formatScore(float $value, int $decimals = 2): string
    {
        $rounded = round($value, $decimals);

        if (abs($rounded - (int) $rounded) < 1e-9) {
            return (string) (int) $rounded;
        }

        return number_format($rounded, $decimals, '.', '');
    }
}
