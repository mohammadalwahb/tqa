<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; }
        .certificate-page {
            width: {{ $width }}px;
            height: {{ $height }}px;
            position: relative;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            @php
                if ($template->backgroundAbsolutePath()) {
                    $mime = mime_content_type($template->backgroundAbsolutePath()) ?: 'image/jpeg';
                    $data = base64_encode(file_get_contents($template->backgroundAbsolutePath()));
                    echo "background-image:url('data:{$mime};base64,{$data}');";
                }
            @endphp
        }
        .certificate-field {
            position: absolute;
            overflow: hidden;
            line-height: 1.2;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="certificate-page">
        @foreach($fields as $field)
            <div class="certificate-field"
                 style="left:{{ $field['x'] }}px;top:{{ $field['y'] }}px;width:{{ $field['width'] }}px;
                        font-size:{{ $field['font_size'] }}px;font-weight:{{ $field['font_weight'] }};
                        color:{{ $field['color'] }};text-align:{{ $field['text_align'] }};">
                @pdfText($field['value'])
            </div>
        @endforeach
    </div>
</body>
</html>
