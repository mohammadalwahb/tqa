@php
    $rtl = \App\Support\LocaleHelper::isRtl();
    $arabicFont = \App\Support\PdfTextHelper::arabicFontPath();
    $arabicFontUrl = $arabicFont ? 'file://' . str_replace('\\', '/', $arabicFont) : null;
@endphp
<style>
    @if($arabicFontUrl)
    @font-face {
        font-family: 'Noto Sans Arabic';
        font-style: normal;
        font-weight: normal;
        src: url('{{ $arabicFontUrl }}') format('truetype');
    }
    @endif
    * {
        font-family: {{ $rtl && $arabicFontUrl ? "'Noto Sans Arabic', 'DejaVu Sans', sans-serif" : "'DejaVu Sans', sans-serif" }};
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
