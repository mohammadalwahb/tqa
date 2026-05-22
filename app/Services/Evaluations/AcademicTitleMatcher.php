<?php

namespace App\Services\Evaluations;

class AcademicTitleMatcher
{
    /**
     * Normalize academic titles so equivalent labels match
     * (e.g. "Assistant Prof." and "Assistant Professor").
     */
    public static function canonical(?string $title): ?string
    {
        $normalized = self::normalize($title);

        if ($normalized === null) {
            return null;
        }

        if (str_contains($normalized, 'assistant') && self::containsProfessorToken($normalized)) {
            return 'assistant_professor';
        }

        if (str_contains($normalized, 'associate') && self::containsProfessorToken($normalized)) {
            return 'associate_professor';
        }

        if (str_contains($normalized, 'lecturer')) {
            return 'lecturer';
        }

        if (self::containsProfessorToken($normalized)) {
            return 'professor';
        }

        return $normalized;
    }

    public static function matches(?string $left, ?string $right): bool
    {
        $leftKey  = self::canonical($left);
        $rightKey = self::canonical($right);

        return $leftKey !== null && $leftKey === $rightKey;
    }

    private static function normalize(?string $title): ?string
    {
        $title = mb_strtolower(trim((string) $title));

        if ($title === '') {
            return null;
        }

        $title = str_replace(['.', ',', '-', '_'], ' ', $title);
        $title = preg_replace('/\s+/', ' ', $title) ?? $title;

        return trim($title);
    }

    private static function containsProfessorToken(string $title): bool
    {
        return str_contains($title, 'professor') || preg_match('/\bprof\b/u', $title) === 1;
    }
}
