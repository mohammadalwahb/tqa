<?php

namespace App\Services\Pdf;

use App\Support\LocaleHelper;
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

    public static function installedArabicFontBasename(): ?string
    {
        $manifest = storage_path('fonts/installed-fonts.json');
        if (! is_readable($manifest)) {
            return null;
        }

        $families = json_decode((string) file_get_contents($manifest), true);
        if (! is_array($families)) {
            return null;
        }

        $basename = $families[self::ARABIC_FONT_FAMILY]['normal'] ?? null;

        return is_string($basename) && $basename !== '' ? $basename : null;
    }

    public static function installedArabicFontPath(): ?string
    {
        $basename = self::installedArabicFontBasename();
        if (! $basename) {
            return null;
        }

        $path = storage_path('fonts/' . $basename . '.ttf');

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
        return self::installedArabicFontPath() !== null
            && is_readable(storage_path('fonts/installed-fonts.json'));
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

    /**
     * Configure DomPDF before HTML is loaded (RTL + Arabic/Kurdish text).
     */
    public static function prepareDompdf(Dompdf $dompdf): void
    {
        self::ensureFontMetricsInstalled();
        self::registerArabicFont($dompdf);

        if (! LocaleHelper::isRtl()) {
            return;
        }

        $options = $dompdf->getOptions();
        $options->setDefaultFont(self::ARABIC_FONT_FAMILY);
        $options->set('enable_font_subsetting', true);
    }

    public static function makeDompdf(): Dompdf
    {
        self::ensureFontDirectories();

        $options = new \Dompdf\Options();
        $options->set('fontDir', storage_path('fonts'));
        $options->set('fontCache', storage_path('fonts'));
        $options->set('chroot', realpath(base_path()) ?: base_path());
        $options->setDefaultFont('dejavu sans');

        return new Dompdf($options);
    }
}
