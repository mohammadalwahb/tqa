<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    @php
        $dpi = (float) config('dompdf.options.dpi', 96);
        $widthPt = round($width * 72 / $dpi, 2);
        $heightPt = round($height * 72 / $dpi, 2);
        $backgroundCss = '';
        if ($template->backgroundAbsolutePath()) {
            $mime = mime_content_type($template->backgroundAbsolutePath()) ?: 'image/jpeg';
            $data = base64_encode(file_get_contents($template->backgroundAbsolutePath()));
            $backgroundCss = "background-image:url('data:{$mime};base64,{$data}');";
        }
    @endphp
    <style>
        @page {
            margin: 0;
            size: {{ $widthPt }}pt {{ $heightPt }}pt;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
            direction: ltr;
        }
        .certificate-page {
            width: {{ $width }}px;
            height: {{ $height }}px;
            max-width: {{ $width }}px;
            max-height: {{ $height }}px;
            position: relative;
            overflow: hidden;
            page-break-after: always;
            page-break-inside: avoid;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            {!! $backgroundCss !!}
        }
        .certificate-page:last-child {
            page-break-after: avoid;
        }
        .certificate-field {
            position: absolute;
            overflow: hidden;
            line-height: 1.25;
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            box-sizing: border-box;
            font-family: 'DejaVu Sans', sans-serif;
            direction: ltr;
            unicode-bidi: embed;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    @foreach($pages as $page)
        <div class="certificate-page">
            @foreach($page['fields'] as $field)
                <div class="certificate-field"
                     style="left:{{ $field['x'] }}px;top:{{ $field['y'] }}px;width:{{ $field['width'] }}px;height:{{ $field['height'] ?? 48 }}px;
                            font-size:{{ $field['font_size'] }}px;font-weight:{{ $field['font_weight'] }};
                            color:{{ $field['color'] }};text-align:{{ $field['text_align'] }};">
                    @if(\App\Support\PdfTextHelper::containsArabicScript((string) ($field['value'] ?? '')))
                        @pdfText($field['value'])
                    @else
                        {{ $field['value'] }}
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
