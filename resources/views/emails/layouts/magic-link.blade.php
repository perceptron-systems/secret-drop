<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background-color: #f3f4f6;
            margin: 0;
            padding: 16px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }
        .accent-line {
            height: 3px;
            background: linear-gradient(90deg, transparent, @yield('gradient-start', '#8b5cf6'), @yield('gradient-end', '#6366f1'), @yield('gradient-start', '#8b5cf6'), transparent);
        }
        .header {
            background: linear-gradient(180deg, @yield('header-bg-start', 'rgba(139, 92, 246, 0.06)'), @yield('header-bg-end', 'rgba(99, 102, 241, 0.02)'));
            padding: 36px 32px 28px;
            text-align: center;
        }
        .logo-icon {
            display: inline-block;
            width: 56px;
            height: 56px;
            border-radius: 16px;
            margin-bottom: 16px;
        }
        .header h1 {
            color: #111827;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.03em;
        }
        .header-sub {
            color: #6b7280;
            font-size: 14px;
            margin: 6px 0 0;
        }
        .badge {
            display: inline-block;
            background: linear-gradient(135deg, @yield('gradient-start', '#8b5cf6'), @yield('gradient-end', '#6366f1'));
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            margin-top: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 32px;
        }
        .content p {
            margin: 0 0 16px;
            color: #4b5563;
            font-size: 15px;
        }
        .button-wrapper {
            text-align: center;
            margin: 28px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, @yield('gradient-start', '#8b5cf6'), @yield('gradient-end', '#6366f1'));
            color: #ffffff !important;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: -0.01em;
            box-shadow: 0 4px 14px @yield('button-shadow', 'rgba(139, 92, 246, 0.35)');
        }
        .features {
            display: table;
            width: 100%;
            margin: 8px 0 0;
            border-spacing: 8px 0;
        }
        .feature {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 10px 4px;
            background-color: #f9fafb;
            border-radius: 10px;
            font-size: 12px;
            color: #6b7280;
            vertical-align: middle;
        }
        .feature-icon {
            display: block;
            font-size: 18px;
            margin-bottom: 2px;
        }
        .warning {
            background-color: #fefce8;
            border-left: 3px solid #eab308;
            padding: 12px 16px;
            margin: 24px 0 0;
            border-radius: 0 10px 10px 0;
        }
        .warning p {
            margin: 0 !important;
            color: #854d0e !important;
            font-size: 13px !important;
        }
        .url-section {
            margin-top: 24px;
        }
        .url-label {
            font-size: 13px;
            color: #9ca3af;
            margin: 0 0 8px;
        }
        .url-box {
            background-color: #f9fafb;
            border: 1px solid #f3f4f6;
            border-radius: 10px;
            padding: 12px 16px;
            word-break: break-all;
            font-family: ui-monospace, 'SF Mono', SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
        }
        .url-box a {
            color: #6b7280;
            text-decoration: none;
        }
        .footer {
            padding: 24px 32px;
            text-align: center;
            background-color: #f9fafb;
            border-top: 1px solid #f3f4f6;
        }
        .footer p {
            margin: 0;
            color: #9ca3af;
            font-size: 12px;
            line-height: 1.5;
        }
        .footer-brand {
            color: #9ca3af;
            font-size: 11px;
            margin-top: 6px;
        }
        .footer-brand a {
            color: @yield('gradient-start', '#8b5cf6');
            text-decoration: none;
            font-weight: 500;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background-color: #0f172a;
            }
            .container {
                background-color: #1e293b;
                box-shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
            }
            .header {
                background: linear-gradient(180deg, @yield('header-bg-dark-start', 'rgba(139, 92, 246, 0.12)'), @yield('header-bg-dark-end', 'rgba(99, 102, 241, 0.04)'));
            }
            .header h1 {
                color: #f1f5f9;
            }
            .header-sub {
                color: #94a3b8;
            }
            .content p {
                color: #cbd5e1;
            }
            .feature {
                background-color: #0f172a;
                color: #94a3b8;
            }
            .warning {
                background-color: #422006;
                border-left-color: #ca8a04;
            }
            .warning p {
                color: #fef08a !important;
            }
            .url-box {
                background-color: #0f172a;
                border-color: #334155;
            }
            .url-box a {
                color: #94a3b8;
            }
            .footer {
                background-color: #162031;
                border-top-color: #334155;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="accent-line"></div>

        <div class="header">
            <img src="{{ asset($__env->yieldContent('logo', 'icon-192.png')) }}" alt="" width="56" height="56" class="logo-icon" style="width:56px;height:56px;border-radius:16px;">
            <h1>{{ config('app.name') }}</h1>
            <p class="header-sub">@yield('header-sub', __('messages.app_tagline'))</p>
            @yield('badge')
        </div>

        <div class="content">
            <p>@yield('intro')</p>

            <div class="button-wrapper">
                <a href="{{ $verifyUrl }}" class="button">@yield('button-text')</a>
            </div>

            <div class="features">
                <div class="feature">
                    <span class="feature-icon">&#x1F512;</span>
                    {{ __('messages.email_feature_encrypted') }}
                </div>
                <div class="feature">
                    <span class="feature-icon">&#x1F441;</span>
                    {{ __('messages.email_feature_zero_knowledge') }}
                </div>
                <div class="feature">
                    <span class="feature-icon">&#x23F1;</span>
                    {{ __('messages.email_feature_ephemeral') }}
                </div>
            </div>

            <div class="warning">
                <p>{{ __('messages.email_magic_link_warning', ['minutes' => config('secrets.magic_link_ttl')]) }}</p>
            </div>

            <div class="url-section">
                <p class="url-label">{{ __('messages.email_link_label') }}</p>
                <div class="url-box">
                    <a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>{{ __('messages.email_footer', ['app' => config('app.name')]) }}</p>
            <p class="footer-brand">&copy; 2026 <a href="{{ url('/') }}">{{ config('app.name') }}</a></p>
        </div>
    </div>
</body>
</html>
