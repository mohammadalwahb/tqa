@php
    use App\Support\LocaleHelper;
    $current = LocaleHelper::current();
    $labels = [
        'en' => __('locale.english'),
        'ar' => __('locale.arabic'),
        'ku' => __('locale.kurdish'),
    ];
@endphp
<div class="dropdown">
    <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center gap-1"
            type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-translate"></i>
        <span class="d-none d-md-inline">{{ $labels[$current] ?? __('locale.language') }}</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><h6 class="dropdown-header">{{ __('locale.language') }}</h6></li>
        @foreach(LocaleHelper::SUPPORTED as $code)
            <li>
                <a class="dropdown-item {{ $current === $code ? 'active' : '' }}"
                   href="{{ route('locale.switch', $code) }}">
                    {{ $labels[$code] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
