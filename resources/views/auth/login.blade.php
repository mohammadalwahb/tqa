<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in · {{ config('app.name', 'TQA') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: #fff;
        }
        .login-card {
            background: #fff;
            color: #0f172a;
            border-radius: 1rem;
            box-shadow: 0 20px 50px rgba(0,0,0,.3);
            padding: 2.5rem;
            max-width: 440px;
            width: 100%;
        }
        .brand { display: flex; gap: .75rem; align-items: center; margin-bottom: 1.5rem; }
        .brand i { font-size: 2rem; color: #1d4ed8; }
        .brand .title { font-size: 1.5rem; font-weight: 700; margin: 0; }
        .btn-google {
            background: #fff;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            font-weight: 500;
            padding: .65rem 1rem;
            border-radius: .5rem;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .65rem;
        }
        .btn-google:hover { background: #f8fafc; }
        .domain-chip {
            display: inline-block;
            background: #eef2ff;
            color: #312e81;
            padding: .25rem .65rem;
            border-radius: 999px;
            font-size: .8rem;
            margin: .2rem .2rem 0 0;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="brand">
        <i class="bi bi-mortarboard-fill"></i>
        <div>
            <p class="title mb-0">{{ config('app.name', 'TQA') }}</p>
            <p class="text-muted small mb-0">Teaching Quality Assessment</p>
        </div>
    </div>

    <h2 class="h5">Sign in to your account</h2>
    <p class="text-muted small mb-4">
        Use your university Google account to sign in. Only authorised university emails
        are permitted.
    </p>

    @if ($errors->any())
        <div class="alert alert-danger small">
            <i class="bi bi-exclamation-octagon me-1"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <a href="{{ route('auth.google.redirect') }}" class="btn btn-google">
        <svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.4 29.3 35.5 24 35.5c-6.4 0-11.5-5.1-11.5-11.5S17.6 12.5 24 12.5c2.9 0 5.6 1.1 7.6 2.9l5.7-5.7C33.6 6.3 29.1 4.5 24 4.5 13.2 4.5 4.5 13.2 4.5 24S13.2 43.5 24 43.5 43.5 34.8 43.5 24c0-1.2-.1-2.3-.4-3.5z"/>
            <path fill="#FF3D00" d="M6.3 14.1l6.6 4.8C14.7 15.2 19 12.5 24 12.5c2.9 0 5.6 1.1 7.6 2.9l5.7-5.7C33.6 6.3 29.1 4.5 24 4.5c-7.4 0-13.7 4.2-17.7 9.6z"/>
            <path fill="#4CAF50" d="M24 43.5c5 0 9.6-1.9 13-5l-6-5.1c-1.9 1.4-4.4 2.2-7 2.2-5.3 0-9.7-3.1-11.3-7.5l-6.5 5C9.7 39.5 16.3 43.5 24 43.5z"/>
            <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.3 4.1-4.3 5.4l6 5.1C40.7 35.5 43.5 30.2 43.5 24c0-1.2-.1-2.3-.4-3.5z"/>
        </svg>
        Continue with Google
    </a>

    <hr class="my-4">

    <p class="small text-muted mb-2">Allowed sign-in domains:</p>
    <div>
        @foreach($allowedDomains as $domain)
            <span class="domain-chip">@<span>{{ $domain }}</span></span>
        @endforeach
    </div>
</div>
</body>
</html>
