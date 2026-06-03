<?php

namespace App\Services\Pdf;

use Dompdf\Dompdf;
use Illuminate\Support\Facades\File;

class DomPdfFontRegistrar
{
    public const ARABIC_FONT_FAMILY = 'noto sans arabic';

    public static function ensureFontDirectories(): void
    {
        File::ensureDirectoryExists(storage_path('fonts'));
    }

    public static function arabicFontSourcePath(): ?string
    {
        $path = resource_path('fonts/NotoSansArabic-Regular.ttf');

        return is_readable($path) ? $path : null;
    }

    public static function installArabicFontMetrics(): void
    {
        self::ensureFontDirectories();

        $source = self::arabicFontSourcePath();
        if (! $source) {
            return;
        }

        $dompdf = self::makeDompdf();
        self::registerArabicFont($dompdf, $source);
    }

    public static function ensureFontMetricsInstalled(): void
    {
        self::ensureFontDirectories();

        if (self::hasInstalledArabicMetrics()) {
            return;
        }

        self::installArabicFontMetrics();
    }

    public static function hasInstalledArabicMetrics(): bool
    {
        $metrics = glob(storage_path('fonts/noto_sans_arabic_*.ufm')) ?: [];

        return is_file(storage_path('fonts/installed-fonts.json')) && $metrics !== [];
    }

    public static function registerArabicFont(Dompdf $dompdf, ?string $source = null): void
    {
        self::ensureFontDirectories();

        $source ??= self::arabicFontSourcePath();
        if (! $source) {
            return;
        }

        $dompdf->getFontMetrics()->registerFont(
            [
                'family' => self::ARABIC_FONT_FAMILY,
                'style'  => 'normal',
                'weight' => 'normal',
            ],
            $source,
        );
    }

    public static function makeDompdf(): Dompdf
    {
        self::ensureFontDirectories();

        $options = new \Dompdf\Options();
        $options->set('fontDir', storage_path('fonts'));
        $options->set('fontCache', storage_path('fonts'));
        $options->set('chroot', realpath(base_path()) ?: base_path());

        return new Dompdf($options);
    }
}
