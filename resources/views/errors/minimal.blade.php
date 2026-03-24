<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Erreur' }} - {{ config('app.name') }}</title>
    <meta name="robots" content="noindex, nofollow">
    <style nonce="{{ csp_nonce() }}">
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { height: 100%; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: linear-gradient(135deg, #0f172a, #1e293b, #0f172a);
            color: #e2e8f0;
        }
        .card {
            max-width: 28rem;
            width: 100%;
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(71, 85, 105, 0.5);
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            text-align: center;
            padding: 3rem 2rem;
        }
        .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            border-radius: 1rem;
            background: rgba(239, 68, 68, 0.1);
            margin-bottom: 1.5rem;
        }
        .icon svg { width: 2rem; height: 2rem; color: #ef4444; }
        .code {
            font-size: 4.5rem;
            font-weight: 700;
            color: rgba(71, 85, 105, 0.7);
            margin-bottom: 1rem;
            line-height: 1;
        }
        h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.75rem;
        }
        .message {
            color: #94a3b8;
            margin-bottom: 2rem;
            line-height: 1.5;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(to right, #7c3aed, #4f46e5);
            color: #fff;
            font-weight: 500;
            border-radius: 0.75rem;
            text-decoration: none;
            box-shadow: 0 10px 15px -3px rgba(124, 58, 237, 0.25);
            transition: opacity 0.15s;
        }
        .btn:hover { opacity: 0.9; }
        .btn svg { width: 1.25rem; height: 1.25rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon" aria-hidden="true">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <p class="code">{{ $code ?? '500' }}</p>
        <h1>{{ $title ?? __('messages.error_server_title') }}</h1>
        <p class="message">{{ $message ?? __('messages.error_server_message') }}</p>
        <a href="/" class="btn">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            {{ __('messages.error_back_home') }}
        </a>
    </div>
</body>
</html>
