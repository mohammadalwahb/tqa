<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ \App\Support\LocaleHelper::direction() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $period->name }} — {{ __('certificates.certificate') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { margin: 0; background: #e2e8f0; font-family: 'Segoe UI', system-ui, sans-serif; }
        .certificate-toolbar { padding: 1rem; }
        .certificate-viewport { overflow: auto; padding: 0 1rem 1rem; }
        .certificate-page {
            margin: 0 auto;
            position: relative;
            box-shadow: 0 4px 24px rgba(0,0,0,.15);
            background-color: #fff;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            @if($template->backgroundUrl())
            background-image: url('{{ $template->backgroundUrl() }}');
            @endif
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
    @if($preview ?? false)
        <div class="certificate-toolbar d-flex gap-2">
            <button type="button" class="btn btn-light btn-sm" onclick="window.close()"><i class="bi bi-x-lg"></i> {{ __('common.close') }}</button>
        </div>
    @else
        <div class="certificate-toolbar d-flex gap-2">
            <a href="{{ route('certificates.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> {{ __('common.back') }}</a>
            <a href="{{ route('certificates.download.pdf', $period) }}" class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> {{ __('certificates.download_pdf') }}</a>
        </div>
    @endif

    <div class="certificate-viewport">
        <div class="certificate-page" style="width:{{ $width }}px;height:{{ $height }}px;">
            @foreach($fields as $field)
                <div class="certificate-field"
                     style="left:{{ $field['x'] }}px;top:{{ $field['y'] }}px;width:{{ $field['width'] }}px;
                            font-size:{{ $field['font_size'] }}px;font-weight:{{ $field['font_weight'] }};
                            color:{{ $field['color'] }};text-align:{{ $field['text_align'] }};">
                    {{ $field['value'] }}
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
