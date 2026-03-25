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
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, #ef4444, #e11d48);
            box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.25);
            margin-bottom: 1.5rem;
        }
        .icon svg { width: 1.75rem; height: 1.75rem; color: #fff; }
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
            <x-icon.exclamation-circle />
        </div>
        <p class="code">{{ $code ?? '500' }}</p>
        <h1>{{ $title ?? __('messages.error_server_title') }}</h1>
        <p class="message">{{ $message ?? __('messages.error_server_message') }}</p>
        <a href="/" class="btn">
            <x-icon.home />
            {{ __('messages.error_back_home') }}
        </a>
    </div>
</body>
</html>
