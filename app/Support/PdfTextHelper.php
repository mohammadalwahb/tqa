<?php

namespace App\Support;

use ArPHP\I18N\Arabic;

class PdfTextHelper
{
    private static ?Arabic $glyphs = null;

    /**
     * Shape Arabic-script text and escape for DomPDF HTML output.
     */
    public static function present(mixed $text): string
    {
        $text = (string) ($text ?? '');

        if ($text === '') {
            return '';
        }

        if (self::containsArabicScript($text)) {
            self::$glyphs ??= new Arabic('Glyphs');
            $text = self::$glyphs->utf8Glyphs($text);
        }

        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function containsArabicScript(string $text): bool
    {
        return (bool) preg_match(
            '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u',
            $text
        );
    }

    public static function arabicFontPath(): ?string
    {
        $path = resource_path('fonts/NotoSansArabic-Regular.ttf');

        return is_readable($path) ? $path : null;
    }
}
