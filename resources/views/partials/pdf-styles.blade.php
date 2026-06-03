@php
    use App\Services\Pdf\DomPdfFontRegistrar;

    $rtl = \App\Support\LocaleHelper::isRtl();
    $useArabicFont = $rtl && DomPdfFontRegistrar::arabicFontSourcePath() !== null;
@endphp
<style>
    * {
        font-family: {{ $useArabicFont ? "'" . DomPdfFontRegistrar::ARABIC_FONT_FAMILY . "', 'DejaVu Sans', sans-serif" : "'DejaVu Sans', sans-serif" }};
    }
    body {
        font-size: 10px;
        color: #1f2937;
        direction: {{ $rtl ? 'rtl' : 'ltr' }};
        text-align: {{ $rtl ? 'right' : 'left' }};
    }
    body.ltr-table table th,
    body.ltr-table table td { text-align: left; }
    body.ltr-table .right { text-align: right; }
    body.ltr-table .center { text-align: center; }
</style>
