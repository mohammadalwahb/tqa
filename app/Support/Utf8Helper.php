<?php

namespace App\Support;

final class Utf8Helper
{
    /** @var list<string> */
    private const PREFERRED_ENCODINGS = ['UTF-8', 'Windows-1256', 'CP1256', 'ISO-8859-6', 'Windows-1252'];

    public static function toUtf8(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = str_replace("\xEF\xBB\xBF", '', $value);
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (self::isCorruptedPlaceholder($value)) {
            return $value;
        }

        if (mb_check_encoding($value, 'UTF-8') && ! self::looksLikeMojibake($value)) {
            return $value;
        }

        $encodings = self::supportedEncodings();

        $detected = mb_detect_encoding($value, $encodings, true);
        if ($detected && $detected !== 'UTF-8') {
            $converted = mb_convert_encoding($value, 'UTF-8', $detected);
            if (is_string($converted) && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        foreach ($encodings as $encoding) {
            if ($encoding === 'UTF-8') {
                continue;
            }

            $converted = @mb_convert_encoding($value, 'UTF-8', $encoding);
            if (! is_string($converted) || ! mb_check_encoding($converted, 'UTF-8')) {
                continue;
            }

            if (! self::looksLikeMojibake($converted)) {
                return $converted;
            }
        }

        return mb_scrub($value, 'UTF-8');
    }

    private static function isCorruptedPlaceholder(string $value): bool
    {
        return (bool) preg_match('/^(\?\s*)+$/u', $value) || (bool) preg_match('/^(\?{2,}\s*)+$/u', $value);
    }

    private static function looksLikeMojibake(string $value): bool
    {
        return (bool) preg_match('/[ÃÂØÙÚÛÜÝÞßãæçèéêëðñòóôõö÷øùúûüýþ]{2,}/u', $value);
    }

    /** @return list<string> */
    private static function supportedEncodings(): array
    {
        static $encodings = null;

        if ($encodings !== null) {
            return $encodings;
        }

        $available = array_flip(mb_list_encodings());

        $encodings = array_values(array_filter(
            self::PREFERRED_ENCODINGS,
            static fn (string $encoding) => isset($available[$encoding])
                || isset($available[strtoupper($encoding)])
                || isset($available[str_replace('-', '', $encoding)])
        ));

        return $encodings = $encodings !== [] ? $encodings : ['UTF-8'];
    }
}
