@php
    use App\Services\Pdf\DomPdfFontRegistrar;

    $rtl = \App\Support\LocaleHelper::isRtl();
    $arabicFontPath = DomPdfFontRegistrar::installedArabicFontPath();
    $useArabicFont = $rtl && $arabicFontPath !== null;
    $arabicFontUrl = $arabicFontPath ? 'file://' . str_replace('\\', '/', $arabicFontPath) : null;
@endphp
<style>
    @if($useArabicFont && $arabicFontUrl)
    @font-face {
        font-family: '{{ DomPdfFontRegistrar::ARABIC_FONT_FAMILY }}';
        font-style: normal;
        font-weight: normal;
        src: url('{{ $arabicFontUrl }}') format('truetype');
    }
    @endif
    @if($useArabicFont)
    html, body, body * {
        font-family: '{{ DomPdfFontRegistrar::ARABIC_FONT_FAMILY }}' !important;
    }
    @else
    html, body, body * {
        font-family: 'DejaVu Sans', sans-serif;
    }
    @endif
    body {
        font-size: 10px;
        color: #1f2937;
        direction: {{ $rtl ? 'rtl' : 'ltr' }};
        text-align: {{ $rtl ? 'right' : 'left' }};
        unicode-bidi: embed;
    }
    body.ltr-table table th,
    body.ltr-table table td { text-align: left; }
    body.ltr-table .right { text-align: right; }
    body.ltr-table .center { text-align: center; }
</style>
